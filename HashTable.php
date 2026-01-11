<?php
// HashTable.php

class GymHashTable {
    private $buckets;
    private $size;

    public function __construct($size = 10) {
        $this->size = $size;
        $this->buckets = array_fill(0, $size, null);
    }

    // Hàm băm đơn giản: Chuyển string thành số index
    private function hash($key) {
        $hash = 0;
        // Cộng dồn mã ASCII của từng ký tự trong tên nhóm cơ
        for ($i = 0; $i < strlen($key); $i++) {
            $hash += ord($key[$i]);
        }
        return $hash % $this->size;
    }

    // Thêm bài tập vào bảng băm theo nhóm cơ
    public function insert($muscleGroup, $exerciseData) {
        $index = $this->hash($muscleGroup);

        // Nếu vị trí này chưa có gì, tạo mảng mới
        if ($this->buckets[$index] === null) {
            $this->buckets[$index] = [];
        }

        // Xử lý va chạm (Collision): Thêm vào danh sách tại index đó
        // Cấu trúc: Mỗi bucket chứa các phần tử dạng ['key' => 'chest', 'data' => ExerciseObj]
        $this->buckets[$index][] = [
            'key' => $muscleGroup,
            'data' => $exerciseData
        ];
    }

    // Tìm kiếm bài tập theo nhóm cơ
    public function search($muscleGroup) {
        $index = $this->hash($muscleGroup);
        $bucket = $this->buckets[$index];
        $results = [];

        if ($bucket !== null) {
            foreach ($bucket as $item) {
                // Kiểm tra lại key để chắc chắn đúng nhóm cơ (xử lý va chạm)
                if ($item['key'] === $muscleGroup) {
                    $results[] = $item['data'];
                }
            }
        }
        return $results;
    }
}
?>