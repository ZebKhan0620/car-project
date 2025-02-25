<?php

namespace Classes\Meeting;

use Exception;

class Calendar {
    private $timezone;
    private $slotsFile;
    private $availableSlots;
    private $defaultDurations = [30, 60]; // Simplified durations
    
    // Enhanced business hours configuration
    private $businessHours = [
        'weekdays' => [
            'monday' => ['09:00-12:00', '13:00-17:00'],
            'tuesday' => ['09:00-12:00', '13:00-17:00'],
            'wednesday' => ['09:00-12:00', '13:00-17:00'],
            'thursday' => ['09:00-12:00', '13:00-17:00'],
            'friday' => ['09:00-12:00', '13:00-17:00']
        ],
        'weekend' => [
            'saturday' => ['10:00-12:00', '13:00-15:00'],
            'sunday' => [] // Closed
        ],
        'holidays' => [
            '2024-12-25', // Christmas
            '2024-12-31', // New Year's Eve
            '2025-01-01', // New Year's Day
            // Add more holidays as needed
        ],
        'lunch_break' => ['12:00', '13:00'], // Lunch break time range
        'exceptions' => [
            // Special dates with different hours
            '2024-12-24' => ['09:00-12:00'], // Christmas Eve - half day
            '2024-12-31' => ['09:00-12:00']  // New Year's Eve - half day
        ]
    ];

    // Updated time slots based on business hours
    private function generateTimeSlots($day) {
        $slots = [];
        $dayName = strtolower(date('l', strtotime($day)));
        
        // Check if it's a holiday
        if (in_array($day, $this->businessHours['holidays'])) {
            return $slots; // Return empty slots for holidays
        }
        
        // Check if it's a special exception day
        if (isset($this->businessHours['exceptions'][$day])) {
            $ranges = $this->businessHours['exceptions'][$day];
        } else {
            // Get regular business hours
            $ranges = isset($this->businessHours['weekdays'][$dayName]) 
                ? $this->businessHours['weekdays'][$dayName] 
                : $this->businessHours['weekend'][$dayName];
        }
        
        foreach ($ranges as $range) {
            list($start, $end) = explode('-', $range);
            $current = strtotime($start);
            $endTime = strtotime($end);
            
            while ($current < $endTime) {
                $timeSlot = date('H:i', $current);
                // Skip lunch break
                if (!$this->isLunchBreak($timeSlot)) {
                    $slots[] = $timeSlot;
                }
                $current = strtotime('+30 minutes', $current);
            }
        }
        
        return $slots;
    }
    
    private function isLunchBreak($time) {
        list($lunchStart, $lunchEnd) = $this->businessHours['lunch_break'];
        return $time >= $lunchStart && $time < $lunchEnd;
    }
    
    // Update the existing updateAvailableSlots method to use the new time slots
    private function updateAvailableSlots() {
        $currentDate = new \DateTime();
        $endDate = (new \DateTime())->modify('+30 days');
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $timeSlots = $this->generateTimeSlots($dateStr);
            
            foreach ($timeSlots as $time) {
                $existingSlot = $this->findExistingSlot($dateStr, $time);
                if (!$existingSlot) {
                    $this->availableSlots['slots'][] = [
                        'id' => 'slot_' . uniqid(),
                        'date' => $dateStr,
                        'time' => $time,
                        'available' => true,
                        'timestamp' => strtotime("$dateStr $time"),
                        'durations' => $this->defaultDurations
                    ];
                }
            }
            $currentDate->modify('+1 day');
        }
        
        $this->saveSlots();
    }

    public function __construct($timezone = 'UTC') {
        $this->timezone = $timezone;
        $this->slotsFile = __DIR__ . '/../../../data/slots.json';
        $this->initializeSlotsFile();
    }

    private function initializeSlotsFile() {
        if (!file_exists($this->slotsFile)) {
            if (!is_dir(dirname($this->slotsFile))) {
                mkdir(dirname($this->slotsFile), 0777, true);
            }
            file_put_contents($this->slotsFile, json_encode([]));
        }
    }

    private function loadAvailableSlots() {
        if (!is_dir(dirname($this->slotsFile))) {
            mkdir(dirname($this->slotsFile), 0777, true);
        }

        // Load existing slots
        if (file_exists($this->slotsFile)) {
            $content = file_get_contents($this->slotsFile);
            $existingSlots = json_decode($content, true);
            
            // If file exists but is invalid, initialize it
            if (!$existingSlots || !isset($existingSlots['slots'])) {
                $this->availableSlots = ['slots' => []];
            } else {
                $this->availableSlots = $existingSlots;
            }
        } else {
            $this->availableSlots = ['slots' => []];
        }

        // Update slots for current and future dates
        $this->updateAvailableSlots();
    }

    private function saveSlots($slots = null) {
        if ($slots === null) {
            $slots = $this->availableSlots;
        }
        if (!is_dir(dirname($this->slotsFile))) {
            mkdir(dirname($this->slotsFile), 0777, true);
        }
        file_put_contents($this->slotsFile, json_encode($slots, JSON_PRETTY_PRINT));
    }

    public function reserveSlot($slotId, $date): bool {
        if (!$this->isSlotAvailable($slotId, $date)) {
            return false;
        }

        $bookedSlots = $this->getBookedSlots();
        $slotKey = $this->createSlotKey($date, $slotId);
        $bookedSlots[$slotKey] = true;
        
        return $this->saveBookedSlots($bookedSlots);
    }

    public function releaseSlot($slotId, $date): bool {
        $bookedSlots = $this->getBookedSlots();
        $slotKey = $this->createSlotKey($date, $slotId);
        
        if (isset($bookedSlots[$slotKey])) {
            unset($bookedSlots[$slotKey]);
            return $this->saveBookedSlots($bookedSlots);
        }
        
        return true;
    }

    public function getSlotDateTime($slotId) {
        $slot = $this->getSlot($slotId);
        return $slot['date'] . ' ' . $slot['time'];
    }

    public function findMatchingSlot($date, $duration) {
        $slots = $this->getAvailableSlots($date);
        foreach ($slots as $slot) {
            if ($slot['available'] && in_array($duration, $slot['durations'])) {
                return $slot;
            }
        }
        return null;
    }

    public function isSlotAvailableForDuration($slotId, $duration) {
        foreach ($this->availableSlots['slots'] as $slot) {
            if ($slot['id'] === $slotId) {
                return $slot['available'] && in_array($duration, $slot['durations']);
            }

        }

        return false;
    }

    public function reserveSlotWithDuration($slotId, $duration) {
        if (!$this->isSlotAvailableForDuration($slotId, $duration)) {
            return false;
        }

        foreach ($this->availableSlots['slots'] as &$slot) {
            if ($slot['id'] === $slotId) {
                $slot['available'] = false;
                $this->saveSlots();
                return true;
            }
        }
        return false;
    }

    private function generateDefaultSlots() {
        return [
            'slots' => []
        ];
    }

    public function isSlotAvailable($slotId, $date) {
        $bookedSlots = $this->getBookedSlots();
        $slotKey = $this->createSlotKey($date, $slotId);
        return !isset($bookedSlots[$slotKey]);
    }

    public function getSlot(string $slotId): ?array {
        $allSlots = $this->availableSlots['slots'] ?? [];
        
        foreach ($allSlots as $slot) {
            if ($slot['id'] === $slotId) {
                return $slot;
            }
        }
        
        throw new Exception('Slot not found: ' . $slotId);
    }

    public function getAvailableSlots($date) {
        $bookedSlots = $this->getBookedSlots();
        $allSlots = MeetingConfig::TIME_SLOTS;
        
        // Filter out booked slots for the given date
        return array_filter($allSlots, function($slot) use ($date, $bookedSlots) {
            $slotKey = $this->createSlotKey($date, $slot['id']);
            return !isset($bookedSlots[$slotKey]);
        });
    }

    private function findExistingSlot($date, $time) {
        if (!isset($this->availableSlots['slots'])) {
            return null;
        }

        foreach ($this->availableSlots['slots'] as $slot) {
            if ($slot['date'] === $date && $slot['time'] === $time) {
                return $slot;
            }
        }

        return null;
    }

    private function loadSlots() {
        if (!file_exists($this->slotsFile)) {
            return $this->generateDefaultSlots();
        }
        $content = file_get_contents($this->slotsFile);
        $slots = json_decode($content, true);
        return $slots ?: $this->generateDefaultSlots();
    }

    public function validateSlotBooking($slotId, $carId) {
        if (empty($slotId) || empty($carId)) {
            throw new Exception('Both slot ID and car ID are required');
        }
        // ...existing validation code...
    }

    private function getBookedSlots(): array {
        $content = file_get_contents($this->slotsFile);
        return json_decode($content, true) ?: [];
    }

    private function saveBookedSlots(array $slots): bool {
        return file_put_contents($this->slotsFile, json_encode($slots, JSON_PRETTY_PRINT)) !== false;
    }

    private function createSlotKey($date, $slotId): string {
        return $date . '_' . $slotId;
    }
}