<h1>Our Products</h1>
<div class="products">
    <?php
    if ($conn !== null) {
        $sql = "SELECT * FROM products";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                ?>
                <div class="product-card">
                    <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                    <p><?php echo htmlspecialchars($row['description']); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($row['price'], 2); ?></p>
                </div>
                <?php
            }
        } else {
            echo "<p>No products found.</p>";
        }
    } else {
        // Fallback products when database is not available
        ?>
        <div class="product-card">
            <h2>Sample Product 1</h2>
            <p>This is a demo product. Database connection required for real products.</p>
            <p><strong>Price:</strong> $99.99</p>
        </div>
        <div class="product-card">
            <h2>Sample Product 2</h2>
            <p>Another demo product. Enable mysqli extension to see database products.</p>
            <p><strong>Price:</strong> $149.99</p>
        </div>
        <?php
    }
    ?>
</div>
