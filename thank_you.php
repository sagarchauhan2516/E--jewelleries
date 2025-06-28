<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Your existing CSS styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }

        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
        }

        h1 {
            color: #d4a017;
            margin-bottom: 20px;
            font-size: 2rem;
        }

        p {
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
        }

        strong {
            color: #333;
        }
        .back-button{
            display: inline-block;
            padding: 10px 20px;
            background-color: #d4a017;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #b8860b;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 30px;
            }
            h1{
                font-size: 1.75rem;
            }
            p{
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // Check if the form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // Collect data safely
            $serviceType = htmlspecialchars($_POST['service_type'] ?? '');
            $repairService = htmlspecialchars($_POST['repair_service'] ?? '');

            // Existing fields
            $jewelleryType = htmlspecialchars($_POST['jewellery_type'] ?? '');
            $preferredStyle = htmlspecialchars($_POST['preferred_style'] ?? '');
            $materialUpgrade = is_array($_POST['material_upgrade'] ?? []) ? implode(", ", $_POST['material_upgrade'] ?? []) : '';
            $metalType = htmlspecialchars($_POST['metal_type'] ?? '');
            $weight = htmlspecialchars($_POST['weight'] ?? '');
            $material = htmlspecialchars($_POST['material'] ?? '');

            // NEW: Capture the estimated price
            $estimatedPrice = htmlspecialchars($_POST['estimated_price'] ?? 'N/A');

            // Build the output message
            $output = "<h1>Thank you for your request!</h1>";
            $output .= "<p><strong>Service:</strong> " . $serviceType . "</p>";

            // Conditionally add fields based on which form was likely submitted
            if (!empty($repairService)) {
                $output .= "<p><strong>Repair Service:</strong> " . $repairService . "</p>";
            }
            if (!empty($jewelleryType)) {
                $output .= "<p><strong>Jewellery Type:</strong> " . $jewelleryType . "</p>";
            }
            if (!empty($preferredStyle)) {
                $output .= "<p><strong>Preferred Style:</strong> " . $preferredStyle . "</p>";
            }
            if (!empty($materialUpgrade)) {
                $output .= "<p><strong>Material Upgrade:</strong> " . $materialUpgrade . "</p>";
            }
            if (!empty($metalType)) {
                $output .= "<p><strong>Metal Type:</strong> " . $metalType . "</p>";
            }
            if (!empty($weight)) {
                $output .= "<p><strong>Weight:</strong> " . $weight . " grams</p>";
            }
            if (!empty($material)) {
                $output .= "<p><strong>Material:</strong> " . $material . "</p>";
            }

            // NEW: Display the estimated price
            if ($estimatedPrice !== 'N/A') {
                $output .= "<p><strong>Estimated Price:</strong> ₹" . number_format((float)$estimatedPrice, 2) . "</p>";
                $output .= "<p><em>(This is an initial estimate. Final price may vary upon detailed consultation.)</em></p>";
            }


            echo $output;

        } else {
            echo "<p>Invalid Request. Please submit the form.</p>";
        }
        ?>
        <a href="javascript:history.back()" class="back-button">Go Back</a>
    </div>
</body>
</html>