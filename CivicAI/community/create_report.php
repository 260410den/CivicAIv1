<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Buat Laporan - CivicAI</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f7fa;
            color: #222;
        }

        header {
            background: white;
            border-bottom: 1px solid #ddd;
            padding: 18px 7%;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .logo span {
            color: #2563eb;
        }

        header a {
            text-decoration: none;
            color: #333;
        }

        .container {
            max-width: 750px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .title {
            margin-bottom: 25px;
        }

        .title h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .title p {
            color: #666;
            line-height: 1.5;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px;

            border: 1px solid #d1d5db;
            border-radius: 8px;

            font-size: 15px;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;

            border: none;
            border-radius: 9px;

            background: #2563eb;
            color: white;

            font-size: 16px;
            font-weight: bold;

            cursor: pointer;
        }

        .submit-btn:hover {
            background: #1d4ed8;
        }

        .note {
            margin-top: 15px;
            font-size: 13px;
            color: #777;
        }

    </style>

    <link rel="stylesheet" href="../assets/css/app-theme.css">
</head>


<body>

<header>

    <div class="logo">
        Civic<span>AI</span>
    </div>

    <a href="index.php">
        ← Kembali
    </a>

</header>


<div class="container">

    <div class="title">

        <h1>
            Buat Laporan
        </h1>

        <p>
            Sampaikan masalah yang kamu temukan
            agar dapat ditinjau dan ditangani.
        </p>

    </div>


    <div class="form-card">

        <form action="../api/create_report.php" method="POST" enctype="multipart/form-data">

            <input
                type="hidden"
                name="user_id"
                value="1"
            >


            <div class="form-group">

                <label>
                    Judul Laporan
                </label>

                <input
                    type="text"
                    name="title"
                    placeholder="Contoh: Jalan berlubang di depan sekolah"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Deskripsi
                </label>

                <textarea
                    name="description"
                    placeholder="Jelaskan masalah yang kamu temukan..."
                    required
                ></textarea>

            </div>


            <div class="form-group">

                <label>
                    Kategori
                </label>

                <select name="category" required>

                    <option value="">
                        Pilih kategori
                    </option>

                    <option value="Infrastruktur">
                        Infrastruktur
                    </option>

                    <option value="Sampah">
                        Sampah
                    </option>

                    <option value="Drainase">
                        Drainase
                    </option>

                    <option value="Banjir">
                        Banjir
                    </option>

                    <option value="Lampu Jalan">
                        Lampu Jalan
                    </option>

                    <option value="Fasilitas Umum">
                        Fasilitas Umum
                    </option>

                    <option value="Lingkungan">
                        Lingkungan
                    </option>

                    <option value="Lainnya">
                        Lainnya
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Lokasi
                </label>

                <input
                    type="text"
                    name="location"
                    placeholder="Contoh: Jl. Daan Mogot, Jakarta Barat"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Foto Bukti
                </label>

                <input
                    type="file"
                    name="photo"
                    accept="image/*"
                >

            </div>

            <div class="form-group">

                <label>
                    Video Bukti
                </label>

                <input
                    type="file"
                    name="video"
                    accept="video/*"
                >

            </div>


            <button
                type="submit"
                class="submit-btn"
            >
                Kirim Laporan
            </button>


            <p class="note">
                Untuk prototype, laporan akan dibuat menggunakan
                akun pengguna demo.
            </p>

        </form>

    </div>

</div>

</body>

</html>