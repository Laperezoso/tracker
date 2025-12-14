<?php
require_once "db_connect.php";

class Appliance {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

   
    public function addAppliance($user_id, $name, $brand, $model, $purchase, $expiry) {
        $query = "INSERT INTO appliances (user_id, appliance_name, brand, model, purchase_date, warranty_expiry)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isssss", $user_id, $name, $brand, $model, $purchase, $expiry);
        return $stmt->execute();
    }


public function getAppliances($user_id, $search = "") {
    $sql = "
    SELECT a.*, w.purchase_date, w.warranty_expiry
    FROM appliances a
    LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
    WHERE a.user_id = ? AND (a.appliance_name LIKE ? OR a.brand LIKE ? OR a.model LIKE ?)
    ";

    $stmt = $this->conn->prepare($sql);
    $likeSearch = "%$search%";
    $stmt->bind_param("isss", $user_id, $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    return $stmt->get_result();
}


public function getApplianceById($id) {
    $stmt = $this->conn->prepare("
        SELECT a.*, w.purchase_date, w.warranty_expiry
        FROM appliances a
        LEFT JOIN warranty w ON a.appliance_id = w.appliance_id
        WHERE a.appliance_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}


public function updateAppliance($id, $name, $brand, $model, $purchase_date, $expiry) {
    $sql = "UPDATE appliances 
            SET appliance_name = ?, brand = ?, model = ?, purchase_date = ?, warranty_expiry = ?
            WHERE appliance_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("sssssi", $name, $brand, $model, $purchase_date, $expiry, $id);
    return $stmt->execute();
}

public function deleteAppliance($id, $user_id) {
    $sql = "DELETE FROM appliances WHERE appliance_id = ? AND user_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ii", $id, $user_id);
    return $stmt->execute();
}

public function updateApplianceStatus($id, $status) {
    $stmt = $this->conn->prepare("UPDATE appliances SET status = ? WHERE appliance_id = ?");
    $stmt->bind_param("si", $status, $id);
    return $stmt->execute();
}


}
?>
