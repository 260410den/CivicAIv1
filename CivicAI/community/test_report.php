<!DOCTYPE html>
<html>
<head>
    <title>Test Report CivicAI</title>
    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>
<body>

<h2>Test Kirim Laporan</h2>

<form action="../api/create_report.php" method="POST">

    <label>User ID:</label><br>
    <input type="number" name="user_id" value="1" required>
    <br><br>

    <label>Judul:</label><br>
    <input type="text" name="title" value="Jalan Rusak" required>
    <br><br>

    <label>Deskripsi:</label><br>
    <textarea name="description" required>
Terdapat jalan berlubang yang mengganggu pengguna jalan.
    </textarea>
    <br><br>

    <label>Kategori:</label><br>
    <input type="text" name="category" value="Infrastruktur" required>
    <br><br>

    <label>Lokasi:</label><br>
    <input type="text" name="location" value="Jl. Contoh Jakarta" required>
    <br><br>

    <button type="submit">Kirim Laporan</button>

</form>

</body>
</html>