<?php 
include('functions/connect.php'); 


if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    

    $shopify_res = mysqli_query($conn, "SELECT shopify_product_id FROM products WHERE id = $id");
    $product_data = mysqli_fetch_assoc($shopify_res);
    $shopify_id = $product_data['shopify_product_id'];

    if(!empty($shopify_id)){
        $accessToken = "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";
        $shopUrl = "shanelibasshop.myshopify.com";

        // Shopify API Call 
        $url = "https://$shopUrl/admin/api/2024-01/products/$shopify_id.json";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Shopify-Access-Token: $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // flder image del
    $img_res = mysqli_query($conn, "SELECT image_path FROM product_images WHERE product_id = $id");
    while($img = mysqli_fetch_assoc($img_res)){
        if(file_exists($img['image_path'])){
            unlink($img['image_path']); 
        }
    }
    
    // db sy del phly img phr prodct
    mysqli_query($conn, "DELETE FROM product_images WHERE product_id = $id");
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");

    header("Location: index.php?msg=Deleted");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        body { background-color: #f8fafc; padding: 40px; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 12px; }
        .badge { font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Products Dashboard</h2>
        <div>
            <?php if(isset($_GET['msg'])) echo '<span class="badge bg-info me-3">Action: '.$_GET['msg'].'</span>'; ?>
            <a href="insert_products.php" class="btn btn-primary">Add New Product</a>
        </div>
    </div>

    <div class="card p-4">
        <h4 class="mb-4 text-center">All Products</h4>
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Price</th>
                    <th>Shopify ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image 
                        FROM products p ORDER BY p.id DESC";
                $result = mysqli_query($conn, $sql);

                if(mysqli_num_rows($result) > 0){
                    while($row = mysqli_fetch_assoc($result)){
                       $img = (!empty($row['main_image'])) ? $row['main_image'] : 'https://via.placeholder.com/60';
                ?>
                        <tr>
                            <td><img src="<?php echo $img;?>" class="product-img"></td>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td>Rs. <?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <?php
                                 if ($row['shopify_product_id']) {
                                     echo '<span class="badge bg-success">' . $row['shopify_product_id'] . '</span>';
                                 } else {
                                     echo '<span class="badge bg-secondary">No ID</span>';
                                 }
                                ?>  
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info">Edit</a>
                                <a href="index.php?delete=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure? This will delete from Local DB and Shopify.')">Delete</a>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>