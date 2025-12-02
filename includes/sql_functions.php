                <?php
                    include '../includes/config.php'; // Re-including config.php to get a new connection

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS methadone_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 2
                                AND drug.drugName = 'Methadone'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Methadone Balance: <span style="font-weight: bold; color: #0033CC;">' . $row['methadone_total_qty'] . '&nbsp;mg</span> <span style="font-weight: bold; color: red;">(' . ($row['methadone_total_qty'] / 5) . ' mL)</span></p>';
                        } else {
                            echo '<p>No Methadone stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>
                    <?php
                    include '../includes/config.php'; // Re-including config.php to get a new connection

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS bupren2_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 6
                                AND drug.drugName = 'Buprenorphine 2mg'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Buprenor 2mg Bal: <span style="font-weight: bold; color: #0033CC;">' . $row['bupren2_total_qty'] . '&nbsp;Tablets</strong></p>';
                        } else {
                            echo '<p>No Buprenor 2mg stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>
                    <?php
                    include '../includes/config.php'; // Re-including config.php to get a new connection

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS bupren4_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 7
                                AND drug.drugName = 'Buprenorphine 4mg'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Buprenor 4mg Bal: <span style="font-weight: bold; color: #0033CC;">' . $row['bupren4_total_qty'] . '&nbsp;Tablets</strong></p>';
                        } else {
                            echo '<p>No Buprenor 4mg stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>

                    <?php
                    include '../includes/config.php'; // Re-including config.php to get a new connection

                    // SQL query
                    $sql = "SELECT stock_movements.total_qty AS bupren8_total_qty
                                FROM stock_movements
                                JOIN drug ON stock_movements.drugName = drug.drugName AND stock_movements.drugID = drug.drugID
                                WHERE drug.drugID = 8
                                AND drug.drugName = 'Buprenorphine 8mg'
                                ORDER BY stock_movements.trans_date DESC
                                LIMIT 1";

                    // Execute the query
                    $result = $conn->query($sql);

                    // Check if query was successful
                    if ($result) {
                        // Check if there are rows returned
                        if ($result->num_rows > 0) {
                            // Fetch data from the first row
                            $row = $result->fetch_assoc();

                            // Output the result
                            echo '<p>Buprenor 8mg Bal: <span style="font-weight: bold; color: #0033CC;">' . $row['bupren8_total_qty'] . '&nbsp;Tablets</strong></p>';
                        } else {
                            echo '<p>No Buprenor 8mg stock records found.</p>';
                        }
                    } else {
                        // Output error message if query fails
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }

                    // Close the connection
                    $conn->close();
                    ?>
