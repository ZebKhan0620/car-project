<?php

namespace Classes\Contact;

use Classes\Storage\JsonStorage;

class ContactStorage extends JsonStorage {
    public function __construct() {
        parent::__construct('contacts.json');
    }

    public function saveSubmission(array $data): bool {
        // Add timestamp to the submission
        $data['submitted_at'] = date('Y-m-d H:i:s');
        
        try {
            return $this->create($data);
        } catch (\Exception $e) {
            error_log("[ContactStorage] Save submission error: " . $e->getMessage());
            return false;
        }
    }

    public function getSubmissions(): array {
        return $this->findAll();
    }

    public function getSubmissionById(string $id): ?array {
        return $this->findById($id);
    }

    public function updateSubmissionStatus(string $id, string $status): bool {
        $submission = $this->findById($id);
        if ($submission) {
            $submission['status'] = $status;
            return $this->update($id, $submission);
        }
        return false;
    }
} 