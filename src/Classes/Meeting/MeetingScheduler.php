<?php

namespace Classes\Meeting;

require_once __DIR__ . '/ValidationException.php';
require_once __DIR__ . '/../../Classes/Storage/JsonStorage.php';
require_once __DIR__ . '/../Security/ActivityLogger.php';
require_once __DIR__ . '/../Cars/CarListing.php';
require_once __DIR__ . '/Calendar.php';

use Exception;
use Cars\CarListing;
use Classes\Meeting\ValidationException;
use Classes\Storage\JsonStorage;
use Classes\Security\ActivityLogger;

class MeetingScheduler {
    private $calendar;
    private $storage;
    private $activityLogger;

    public function __construct() {
        $this->calendar = new Calendar();
        $this->storage = new JsonStorage('meetings.json');
        $this->activityLogger = new ActivityLogger();
    }

    private function validateMeetingData(array $data) {
        // Modify required fields to match the actual form data
        $required = ['car_id', 'type', 'slot_id', 'name', 'email', 'timezone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!MeetingConfig::isValidType($data['type'])) {
            throw new Exception('Invalid meeting type');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        if (!$this->calendar->isSlotAvailable($data['slot_id'], $data['date'])) {
            throw new Exception('Selected time slot is not available');
        }
    }

    private function createMeetingRecord(array $data, array $car): array {
        return [
            'id' => 'm' . uniqid(),
            'car_id' => $data['car_id'],
            'dealer_id' => $car['user_id'],
            'type' => $data['type'],
            'slot_id' => $data['slot_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'notes' => $data['notes'] ?? '',
            'timezone' => $data['timezone'],
            'duration' => $data['duration'],
            'status' => 'scheduled',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function setupMeeting(array $meeting): array {
        return array_merge($meeting, [
            'provider' => 'default',
            'provider_meeting_id' => 'meeting_' . uniqid(),
            'join_url' => '#'
        ]);
    }

    public function cancelMeeting(string $meetingId): bool {
        try {
            $meeting = $this->storage->findById($meetingId);
            if (!$meeting) {
                throw new Exception('Meeting not found');
            }

            if ($meeting['status'] !== 'scheduled') {
                throw new Exception('Meeting is not in scheduled status');
            }

            // Update meeting status first
            $meeting['status'] = 'cancelled';
            $meeting['updated_at'] = date('Y-m-d H:i:s');
            
            // Save the updated meeting
            if (!$this->storage->update($meetingId, $meeting)) {
                throw new Exception('Failed to update meeting status');
            }

            // Then try to release the slot
            if (!$this->calendar->releaseSlot($meeting['slot_id'], $meeting['scheduled_date'])) {
                error_log("[MeetingScheduler] Warning: Could not release slot {$meeting['slot_id']}");
                // Don't throw exception here, meeting is already cancelled
            }

            // Log activity
            $this->activityLogger->log('meeting_cancelled', [
                'meeting_id' => $meetingId,
                'car_id' => $meeting['car_id']
            ]);

            return true;

        } catch (Exception $e) {
            error_log("[MeetingScheduler] Cancel error: " . $e->getMessage());
            throw $e;
        }
    }

    private function loadMeetings(): array {
        $meetingsFile = __DIR__ . '/../../../data/meetings.json';
        
        // Create file if it doesn't exist
        if (!file_exists($meetingsFile)) {
            if (!is_dir(dirname($meetingsFile))) {
                mkdir(dirname($meetingsFile), 0777, true);
            }
            file_put_contents($meetingsFile, json_encode([]));
        }
        
        $content = file_get_contents($meetingsFile);
        return json_decode($content, true) ?: [];
    }

    private function saveMeetings(array $meetings): bool {
        $meetingsFile = __DIR__ . '/../../../data/meetings.json';
        $success = file_put_contents($meetingsFile, json_encode($meetings, JSON_PRETTY_PRINT)) !== false;
        error_log("Saved meetings: " . json_encode($meetings)); // Debug log
        return $success;
    }

    public function rescheduleMeeting($meetingId, $newSlotId): bool {
        try {
            error_log("Attempting to reschedule meeting $meetingId to slot $newSlotId");
            
            // Load current meeting data
            $meeting = $this->getMeeting($meetingId);
            if (!$meeting) {
                throw new Exception('Meeting not found');
            }

            // Release old slot
            if (!$this->calendar->releaseSlot($meeting['slot_id'], $meeting['scheduled_date'])) {
                error_log("Warning: Could not release old slot {$meeting['slot_id']}");
            }

            // Reserve new slot
            if (!$this->calendar->reserveSlot($newSlotId, $meeting['scheduled_date'])) {
                // If new slot reservation fails, try to re-reserve old slot
                $this->calendar->reserveSlot($meeting['slot_id'], $meeting['scheduled_date']);
                throw new Exception('New slot is not available');
            }

            // Update meeting data
            $meeting['slot_id'] = $newSlotId;
            $meeting['updated_at'] = date('Y-m-d H:i:s');
            
            // Get slot details
            $slot = $this->calendar->getSlot($newSlotId);
            if ($slot) {
                $meeting['scheduled_date'] = $slot['date'];
                $meeting['scheduled_time'] = $slot['time'];
            }

            // Save updated meeting
            if (!$this->storage->update($meetingId, $meeting)) {
                // If save fails, rollback slot changes
                $this->calendar->releaseSlot($newSlotId, $meeting['scheduled_date']);
                $this->calendar->reserveSlot($meeting['slot_id'], $meeting['scheduled_date']);
                throw new Exception('Failed to save meeting updates');
            }

            error_log("Successfully rescheduled meeting: " . json_encode($meeting));
            return true;
            
        } catch (Exception $e) {
            error_log("Reschedule error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getMeeting(string $meetingId): ?array {
        try {
            $meetings = $this->loadMeetings();
            foreach ($meetings as $meeting) {
                if ($meeting['id'] === $meetingId) {
                    return [
                        'id' => $meeting['id'],
                        'type' => $meeting['type'],
                        'status' => $meeting['status'],
                        'scheduled_date' => $meeting['scheduled_date'],
                        'scheduled_time' => $meeting['scheduled_time'],
                        'name' => $meeting['customer_name'] ?? $meeting['name'] ?? '',
                        'email' => $meeting['customer_email'] ?? $meeting['email'] ?? '',
                        'notes' => $meeting['notes'] ?? '',
                        'car_id' => $meeting['car_id'],
                        'join_url' => $meeting['join_url'] ?? null,
                        'created_at' => $meeting['created_at']
                    ];
                }
            }
            return null;
        } catch (Exception $e) {
            error_log("[MeetingScheduler] Get meeting error: " . $e->getMessage());
            return null;
        }
    }

    public function getUserMeetings(int $userId, ?string $status = null): array {
        try {
            $meetings = $this->storage->findAll();
            return array_filter($meetings, function($meeting) use ($userId, $status) {
                $userMatch = $meeting['dealer_id'] === $userId;
                if ($status) {
                    return $userMatch && $meeting['status'] === $status;
                }
                return $userMatch;
            });
        } catch (Exception $e) {
            error_log("[MeetingScheduler] Get user meetings error: " . $e->getMessage());
            return [];
        }
    }

    public function schedule(array $data): bool {
        try {
            $calendar = new Calendar();
            
            // Debug log
            error_log("Scheduling meeting: " . json_encode($data));

            // Check slot availability with date
            if (!$calendar->isSlotAvailable($data['slot_id'], $data['scheduled_date'])) {
                throw new Exception('Selected time slot is not available');
            }

            // Load existing meetings
            $meetings = $this->loadMeetings();
            
            // Add new meeting with a unique ID
            $slot = array_filter(MeetingConfig::TIME_SLOTS, function($s) use ($data) {
                return $s['id'] == $data['slot_id'];
            });
            $slot = reset($slot);

            // Prepare meeting data
            $meetingData = [
                'id' => 'meeting_' . uniqid(),
                'car_id' => $data['car_id'],
                'type' => $data['type'],
                'slot_id' => $data['slot_id'],
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $slot['time'],
                'name' => $data['customer_name'] ?? $data['name'] ?? '',
                'email' => $data['customer_email'] ?? $data['email'] ?? '',
                'notes' => $data['notes'] ?? '',
                'status' => 'scheduled',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Add join URL for virtual meetings
            if (strpos($data['type'], 'video_call_') === 0) {
                $meetingData['join_url'] = '#';
            }

            // Debug log
            error_log("Created meeting data: " . json_encode($meetingData));

            $meetings[] = $meetingData;
            
            // Save meetings
            if (!$this->saveMeetings($meetings)) {
                throw new Exception('Failed to save meeting data');
            }
            
            // Reserve the slot
            if (!$calendar->reserveSlot($data['slot_id'], $data['scheduled_date'])) {
                throw new Exception('Failed to reserve time slot');
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to schedule meeting: " . $e->getMessage());
            throw new Exception("Failed to schedule meeting: " . $e->getMessage());
        }
    }

    private function getMeetingType(string $meetingType): string {
        return $meetingType === 'dealership_visit' ? 'in_person' : 'online';
    }

    private function normalizeKeys(array $data): array {
        $normalized = [];
        
        // Map all keys to snake_case (database format)
        $key_mapping = [
            'slotId' => 'slot_id',
            'carId' => 'car_id',
            'dealerId' => 'dealer_id'
        ];

        foreach ($data as $key => $value) {
            if (isset($key_mapping[$key])) {
                $normalized[$key_mapping[$key]] = $value;
            } else {
                $normalized[$key] = $value;
            }
        }

        return array_merge([
            'timezone' => 'UTC',
            'notes' => '',
            'duration' => 30
        ], $normalized);
    }

    public function updateMeetingDetails(string $meetingId, array $details): bool {
        try {
            $meeting = $this->storage->findById($meetingId);
            if (!$meeting) {
                throw new Exception('Meeting not found');
            }

            $meeting = array_merge($meeting, $details, [
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $this->storage->update($meetingId, $meeting);
        } catch (Exception $e) {
            error_log("[MeetingScheduler] Update error: " . $e->getMessage());
            return false;
        }
    }

    public function getMeetingsForCar(string $carId): array {
        try {
            $meetings = $this->loadMeetings();
            error_log("All meetings: " . json_encode($meetings));
            error_log("Looking for car ID: " . $carId);
            
            $carMeetings = array_filter($meetings, function($meeting) use ($carId) {
                return $meeting['car_id'] === $carId && 
                       in_array($meeting['status'], ['scheduled', 'confirmed']);
            });

            return array_map(function($meeting) {
                return [
                    'id' => $meeting['id'],
                    'type' => $meeting['type'],
                    'status' => $meeting['status'],
                    'scheduled_date' => $meeting['scheduled_date'],
                    'scheduled_time' => $meeting['scheduled_time'],
                    'name' => $meeting['customer_name'] ?? $meeting['name'] ?? '',
                    'email' => $meeting['customer_email'] ?? $meeting['email'] ?? '',
                    'notes' => $meeting['notes'] ?? '',
                    'join_url' => $meeting['join_url'] ?? null,
                    'created_at' => $meeting['created_at']
                ];
            }, $carMeetings);

        } catch (Exception $e) {
            error_log("[MeetingScheduler] Get car meetings error: " . $e->getMessage());
            return [];
        }
    }

    public function getCalendar(): Calendar {
        return $this->calendar;
    }

    private function saveMeeting(array $meeting): bool {
        // Use insert instead of create
        return $this->storage->insert($meeting);
    }
}