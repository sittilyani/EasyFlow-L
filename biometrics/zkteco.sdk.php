<?php
// zkteco_sdk.php - ZKTeco specific implementation
session_start();
include "../includes/config.php";

class ZKTecoFingerprint {
    private $conn;

    public function __construct($databaseConnection) {
        $this->conn = $databaseConnection;
    }

    public function captureFingerprint($patientId) {
        // This would interface with ZKTeco SDK
        // For now, we'll simulate the process

        $patientData = $this->getPatientData($patientId);

        return [
            'success' => true,
            'patient' => $patientData,
            'instructions' => 'Use ZKTeco SDK to capture fingerprint'
        ];
    }

    private function getPatientData($patientId) {
        $query = "SELECT * FROM patients WHERE p_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function saveFingerprint($data) {
        $sql = "INSERT INTO fingerprints
                (visitDate, mat_id, clientName, sex, current_status,
                 fingerprint_data, scanner_type, capture_date)
                VALUES (?, ?, ?, ?, ?, ?, 'ZKTeco', NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssb",
            $data['visitDate'],
            $data['mat_id'],
            $data['clientName'],
            $data['sex'],
            $data['current_status'],
            $data['fingerprint_data']
        );

        return $stmt->execute();
    }

    public function verifyFingerprint($fingerprintData) {
        // This would use ZKTeco's matching algorithm
        // For now, simulate verification

        $sql = "SELECT * FROM fingerprints WHERE fingerprint_data = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("b", $fingerprintData);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }
}