<?php  
include('functions/connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];

    $sql = "INSERT INTO products (title, description, price) VALUES ('$title', '$description', '$price')";
    
    if (mysqli_query($conn, $sql)) {
        $product_id = mysqli_insert_id($conn); 

        // img ko sv kiya foldr m r ary bnya
        $shopify_images = [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $key => $val) {
                $tmp_name = $_FILES['images']['tmp_name'][$key];
                $file_name = $_FILES['images']['name'][$key];
                $unique_name = time() . "_" . $file_name;
                $upload_path = "uploads/" . $unique_name;

                if (!is_dir('uploads')) { 
                    mkdir('uploads', 0777, true); 
                    }

                if (move_uploaded_file($tmp_name, $upload_path)) {
                    
                    mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$product_id', '$upload_path')");
                    
                    // Shopify ke liye Base64 m cnvert
                    $imageData = base64_encode(file_get_contents($upload_path));
                    $shopify_images[] = ["attachment" => $imageData, "filename" => $file_name];
                }
            }
        }

        // shpfy prod dta
        $accessToken = "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";
        $shopUrl = "shanelibasshop.myshopify.com";
        
        $productData = [
            "product" => [
                "title" => $title,
                "body_html" => $description,
                "vendor" => "Shan-e-Libas",
                "images" => $shopify_images, 
                "variants" => [
                    [
                        "price" => $price,
                        "sku" => "SEL-" . $product_id
                    ]
                ]
            ]
        ];

        //  cURL Call
        $url = "https://$shopUrl/admin/api/2024-01/products.json";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Shopify-Access-Token: $accessToken", "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($productData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        $sync_status = "but Shopify sync failed.";
        if (isset($result['product'])) {
            $shopify_id = $result['product']['id'];
            mysqli_query($conn, "UPDATE products SET shopify_product_id = '$shopify_id' WHERE id = $product_id");
            $sync_status = "& Synced with Shopify (Images Included)!";
        }
        $success_msg = "Product published locally " . $sync_status;
        

    } else {
        $error_msg = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --border: #e2e8f0;
            --success: #22c55e;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .form-container {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }

        .alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        h2 {
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #64748b;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .file-input-wrapper {
            position: relative;
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .file-input-wrapper:hover {
            background: #f1f5f9;
            border-color: var(--primary);
        }

        input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        button {
            width: 100%;
            background-color: var(--primary);
            color: white;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 1rem;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        #preview-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .preview-img {
            width: 100%;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="index.php" style="text-decoration: none; font-size: 0.8rem; color: var(--primary); font-weight: 600;">← Back to Dashboard</a>
    </div>
    <h2>New Product Listing</h2>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Title</label>
            <input type="text" name="title" placeholder="e.g. Vintage Camera" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Tell buyers about your item..."></textarea>
        </div>

        <div class="form-group">
            <label>Price</label>
            <input type="number" name="price" step="0.01" placeholder="0.00">
        </div>

        <div class="form-group">
            <label>Product Images</label>
            <div class="file-input-wrapper">
                <span id="file-label">Click to upload or drag & drop</span>
                <input type="file" name="images[]" id="images" multiple required onchange="handleFiles()">
            </div>
            <div id="preview-container"></div>
        </div>

        <button type="submit">Publish Product</button>
    </form>
</div>

<script>
    function handleFiles() {
        const input = document.getElementById('images');
        const label = document.getElementById('file-label');
        const preview = document.getElementById('preview-container');
        const files = input.files;
        
        label.innerText = files.length > 0 ? `${files.length} file(s) selected` : 'Click to upload or drag & drop';

        preview.innerHTML = '';
        for (let i = 0; i < files.length; i++) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('preview-img');
                preview.appendChild(img);
            }
            reader.readAsDataURL(files[i]);
        }
    }
</script>

</body>
</html>