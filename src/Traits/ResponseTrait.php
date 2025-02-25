<?php

trait ResponseTrait {
    protected function success($message, $data = [], $code = 200) {
        return $this->respond(true, $message, $data, $code);
    }

    protected function error($message, $data = [], $code = 400) {
        return $this->respond(false, $message, $data, $code);
    }

    private function respond($success, $message, $data = [], $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}
