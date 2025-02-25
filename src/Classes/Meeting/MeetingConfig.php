<?php

namespace Classes\Meeting;

class MeetingConfig {
    private static $meetingTypes = [
        'dealership_visit' => [
            'description' => 'In-Person Dealership Visit',
            'duration' => 30
        ],
        'virtual_tour' => [
            'description' => 'Virtual Tour',
            'duration' => 30
        ],
        'video_call' => [
            'description' => 'Video Call Discussion',
            'duration' => 30
        ]
    ];

    public const MEETING_TYPES = [
        'video_call_zoom' => 'Zoom Video Call',
        'video_call_skype' => 'Skype Video Call',
        'video_call_gmeet' => 'Google Meet',
        'dealership_visit' => 'In-Person Dealership Visit'
    ];

    // Add time slots configuration
    const TIME_SLOTS = [
        ['id' => 1, 'time' => '09:00 AM'],
        ['id' => 2, 'time' => '10:00 AM'],
        ['id' => 3, 'time' => '11:00 AM'],
        ['id' => 4, 'time' => '01:00 PM'],
        ['id' => 5, 'time' => '02:00 PM'],
        ['id' => 6, 'time' => '03:00 PM'],
        ['id' => 7, 'time' => '04:00 PM'],
        ['id' => 8, 'time' => '05:00 PM']
    ];

    public static function getMeetingTypes(): array {
        return self::$meetingTypes;
    }

    public static function isValidType(string $type): bool {
        return isset(self::$meetingTypes[$type]);
    }

    public static function isVirtualMeeting(string $type): bool {
        return strpos($type, 'video_call_') === 0;
    }

    public static function getProvider(string $type): ?string {
        $providers = [
            'virtual_tour' => 'zoom',
            'video_call' => 'gmeet'
        ];
        return $providers[$type] ?? null;
    }
}