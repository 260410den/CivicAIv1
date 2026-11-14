<?php
/**
 * CivicAI - AI Service Helper (NLP & Priority Analysis)
 * Prinsip: AI Recommends, Humans Decide.
 */

class AIService {
    
    /**
     * Menganalisis teks laporan menggunakan logika NLP/Heuristik cerdas
     * untuk merekomendasikan kategori dan tingkat prioritas.
     */
    public static function analyzeReportText($title, $description) {
        $text = strtolower($title . " " . $description);
        $score = 50;
        $reasons = [];
        $problem = 'Masalah umum';
        $category = 'Lainnya';
        $risk = 'Dampak operasional belum teridentifikasi';
        $location_context = 'Area umum';
        $duration = 'Belum disebutkan';

        $problem_patterns = [
            'Lampu atau penerangan' => ['lampu', 'penerangan', 'gelap'],
            'Banjir atau genangan' => ['banjir', 'genangan', 'terendam'],
            'Sampah atau kebersihan' => ['sampah', 'limbah', 'kotor', 'bau'],
            'Drainase tersumbat' => ['drainase', 'selokan', 'saluran', 'tersumbat'],
            'Kerusakan jalan' => ['jalan', 'berlubang', 'aspal', 'jembatan'],
        ];
        foreach ($problem_patterns as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $problem = $label;
                    $category = $label === 'Kerusakan jalan' ? 'Infrastruktur' : ($label === 'Banjir atau genangan' ? 'Banjir' : ($label === 'Sampah atau kebersihan' ? 'Kebersihan' : ($label === 'Lampu atau penerangan' ? 'Fasilitas Umum' : ($label === 'Drainase tersumbat' ? 'Drainase' : 'Lainnya'))));
                    break 2;
                }
            }
        }

        $risk_patterns = [
            'Risiko keselamatan jiwa' => ['jiwa', 'kecelakaan', 'bahaya', 'ambruk', 'longsor', 'ledak'],
            'Gangguan lalu lintas' => ['macet', 'pengendara', 'kendaraan', 'jalan'],
            'Risiko kesehatan atau lingkungan' => ['bau', 'limbah', 'tercemar', 'penyakit'],
        ];
        foreach ($risk_patterns as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $risk = $label;
                    break 2;
                }
            }
        }

        if (preg_match('/(\d+)\s*(hari|minggu|bulan)/i', $text, $match)) {
            $duration = $match[1] . ' ' . strtolower($match[2]);
            $score += 8;
            $reasons[] = 'Durasi belum terselesaikan +8';
        }
        if (preg_match('/(sekolah|kampus|rumah sakit|puskesmas|pasar)/i', $text, $match)) {
            $location_context = 'Dekat ' . strtolower($match[1]);
            $score += 20;
            $reasons[] = 'Dekat fasilitas publik +20';
        }
        if ($risk === 'Risiko keselamatan jiwa') {
            $score += 28;
            $reasons[] = 'Risiko keselamatan jiwa +28';
        } elseif ($risk !== 'Dampak operasional belum teridentifikasi') {
            $score += 14;
            $reasons[] = 'Dampak layanan/lingkungan +14';
        }
        if (in_array($problem, ['Kerusakan jalan', 'Banjir atau genangan', 'Drainase tersumbat'], true)) {
            $score += 14;
            $reasons[] = 'Masalah infrastruktur +14';
        }
        if (preg_match('/darurat|kritis|segera|parah|total/i', $text)) {
            $score += 16;
            $reasons[] = 'Indikator urgensi tinggi +16';
        }
        $score = min(100, $score);
        $priority = $score >= 85 ? 'critical' : ($score >= 70 ? 'high' : ($score >= 45 ? 'medium' : 'low'));
        $reasoning = implode('; ', $reasons);
        if ($reasoning === '') {
            $reasoning = 'Belum ada indikator khusus; skor dasar analisis digunakan.';
        }

        $summary = strip_tags($description);
        if (function_exists("mb_strimwidth")) {
            $summary = mb_strimwidth($summary, 0, 100, "...");
        } else {
            $summary = substr($summary, 0, 100);
            if (strlen($summary) >= 100) {
                $summary .= "...";
            }
        }

        return [
            "success" => true,
            "recommended_priority" => $priority,
            "ai_reasoning" => $reasoning,
            "summary" => $summary,
            "priority_score" => $score,
            "problem" => $problem,
            "category" => $category,
            "duration" => $duration,
            "risk" => $risk,
            "location_context" => $location_context
        ];
    }

    /**
     * Integrasi opsional ke External LLM API (Contoh kerangka cURL ke Gemini/OpenAI API)
     */
    public static function callExternalLLM($prompt) {
        $apiKey = getenv('GEMINI_API_KEY') ?: '';
        
        // Jika API Key belum disetting, kembalikan null agar sistem menggunakan analisis lokal
        if ($apiKey === '') {
            return null;
        }

        // Contoh implementasi cURL standar untuk LLM
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
        
        $data = [
            "contents" => [
                [
                    "parts" => [["text" => $prompt]]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? json_decode($response, true) : null;
    }
}
?>
