<?php 
include('functions/connect.php');

$id = $_GET['id']; 

$product_res = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
$product = mysqli_fetch_assoc($product_res);

$images_res = mysqli_query($conn, "SELECT * FROM product_images WHERE product_id = $id");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $stock = 15; 

    if (mysqli_query($conn, "UPDATE products SET title='$title', description='$description',
     price='$price' WHERE id=$id")) {
        
        $shopify_id = $product['shopify_product_id'];
        
        if (!empty($shopify_id)) {
            $accessToken = "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";
            $shopUrl = "shanelibasshop.myshopify.com";

            // Nayi images ko Base64 mein conver
            $shopify_images = [];
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $key => $val) {
                    $tmp_name = $_FILES['images']['tmp_name'][$key];
                    $file_name = $_FILES['images']['name'][$key];
                    $unique_name = time() . "_" . $file_name;
                    $upload_path = "uploads/" . $unique_name;

                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$id', '$upload_path')");
                        
                        $img_binary = file_get_contents($upload_path);
                        $shopify_images[] = [
                            "attachment" => base64_encode($img_binary), 
                            "filename" => $file_name
                        ];
                    }
                }
            }

            // Shopify Data Pack 
            $updateData = [
                    "product" => [
                    "id" => $shopify_id,
                    "title" => $title,
                    "body_html" => $description,
                    "images" => $shopify_images,
                    "variants" => [
                        [
                            "price" => $price,
                            "inventory_management" => "shopify", // Stock tracking on
                            "inventory_quantity" => (int)$stock  // Stock quantity
                        ]
                    ]
                ]
            ];

            $url = "https://$shopUrl/admin/api/2024-01/products/$shopify_id.json";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Shopify-Access-Token: $accessToken", "Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
        }
        
        header("Location: index.php?msg=Updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .old-img-card { position: relative; width: 100px; height: 100px; margin-right: 10px; margin-bottom: 10px; }
        .old-img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .delete-img-btn { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; padding: 0px 6px; font-size: 12px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body class="bg-light p-5">

<div class="container bg-white p-4 rounded shadow-sm" style="max-width: 600px;">
    <h3>Edit Product</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($product['title']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" name="price" class="form-control" value="<?php echo $product['price']; ?>" step="0.01">
        </div>

        <div class="mb-3">
            <label class="form-label d-block">Current Images</label>
            <div class="d-flex flex-wrap">
                <?php 
                if (mysqli_num_rows($images_res) > 0) {
                    while($img = mysqli_fetch_assoc($images_res)): ?>
                        <div class="old-img-card">
                            <img src="<?php echo $img['image_path']; ?>" class="old-img">
                            <a href="delete_single_image.php?img_id=<?php echo $img['id']; ?>&prod_id=<?php echo $id; ?>" class="delete-img-btn" title="Delete Image">×</a>
                        </div>
                    <?php endwhile; 
                } else {
                    echo "<p class='text-muted small'>No images uploaded yet.</p>";
                }
                ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Add More Images</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>

        <button type="submit" class="btn btn-primary w-100">Update Product & Sync Shopify</button>
        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
    </form>
</div>

</body>
</html>