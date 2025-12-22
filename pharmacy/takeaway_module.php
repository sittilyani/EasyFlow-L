<?php
session_start();
include '../includes/config.php';
include '../includes/header.php';

if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo '<div id="message-container" class="alert alert-info">' . htmlspecialchars($message) . '</div>';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy DAR</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", Times, serif;
            margin-left: 20px;
            margin-right: 20px;
        }
        .header {
            margin-bottom: 20px;
        }
        .cancel-input {
            background-color: green;
            color: white;
            width: 80px;
            margin-right: 40px;
            margin-left: 40px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
        }
        #sign-input {
            background-color: red;
            color: white;
            width: 120px;
            margin-right: 10px;
            margin-left: 100px;
            border: none;
            border-radius: 5px;
            height: 37px;
            cursor: pointer;
            padding: 5px;
        }
        h2 {
            margin-bottom: 10px;
        }
        th, td {
            padding: 10px;
        }
        #searchResults {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 5px;
            background-color: #fff;
        }
        .search-result-item {
            cursor: pointer;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .search-result-item:hover {
            background-color: #e0e0e0;
        }
        #patientTableContainer {
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-links a {
            margin-right: 5px;
        }
    </style>
</head>

    <div class="main-content">
    <h2 style="color: #2C3162;">Drugs Dispensing Form</h2>

    <!-- Search Form -->
    <div class="header">
        <label for="searchPatient">Search Patient (MAT ID, Name, etc.):</label>
        <input type="text" id="searchPatient" class="form-control" placeholder="Start typing...">
        <button type="button" onclick="clearSearch()" class="cancel-input">Cancel</button>
        <a href="edit_dispensed_dose.php" id="sign-input">Edit Dispensed Record</a>
        <div id="searchResults"></div>
    </div>

    <!-- Patient Table -->
    <div id="patientTableContainer">
        <table>
            <thead>
                <tr>
                    <th>p_ID</th>
                    <th>MAT ID</th>
                    <th>MAT Number</th>
                    <th>Client Name</th>
                    <th>Date of Birth</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Physical Address</th>
                    <th>CSO</th>
                    <th>Drug Name</th>
                    <th>Dosage</th>
                    <th>Current Status</th>
                    <th>History</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="patientTableBody"></tbody>
        </table>
        <div id="pagination"></div>
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    let currentPage = 1;
    const resultsPerPage = 10;

    // Load patients
    function loadPatients(search = '', page = 1) {
        currentPage = page;
        $.ajax({
            url: 'fetch_patients.php',
            method: 'GET',
            data: { search: search, page: page, results_per_page: resultsPerPage },
            success: function(response) {
                let tableHtml = '';
                $(response).each(function() {
                    const patient = $(this).data();
                    tableHtml += `
                        <tr>
                            <td>${patient.pId || ''}</td>
                            <td>${patient.matId || ''}</td>
                            <td>${patient.matNumber || ''}</td>
                            <td>${patient.name || ''}</td>
                            <td>${patient.dob || ''}</td>
                            <td>${patient.age || ''}</td>
                            <td>${patient.sex || ''}</td>
                            <td>${patient.address || ''}</td>
                            <td>${patient.cso || ''}</td>
                            <td>${patient.drugname || ''}</td>
                            <td>${patient.dosage || '0'}</td>
                            <td>${patient.status || ''}</td>
                            <td><center><a href='history.php?p_id=${patient.pId}' style='font-size: 24px; color: brown;'><i class='fa fa-exclamation-circle'></i></a></center></td>
                            <td class='action-links'>
                                <a href='view.php?p_id=${patient.pId}'>View</a> |
                                <a href='dispensingData.php?mat_id=${patient.matId}'>Dispense</a> |
                                <a href='multi_dispensing.php?mat_id=${patient.matId}'>MDD</a> |
                                <a href='../referrals/referral.php?mat_id=${patient.matId}'>Refer</a>
                            </td>
                        </tr>`;
                });
                $('#patientTableBody').html(tableHtml);

                // Update pagination
                $.ajax({
                    url: 'fetch_patients.php',
                    method: 'GET',
                    data: { search: search, count: true },
                    success: function(countResponse) {
                        const totalResults = parseInt(countResponse);
                        const totalPages = Math.ceil(totalResults / resultsPerPage);
                        const startRange = (currentPage - 1) * resultsPerPage + 1;
                        const endRange = Math.min(currentPage * resultsPerPage, totalResults);
                        let paginationHtml = `<div>Showing ${startRange}-${endRange} of ${totalResults} results</div>`;
                        if (currentPage > 1) {
                            paginationHtml += `<a href='#' onclick='loadPatients("${search}", ${currentPage - 1})'>Previous</a> `;
                        }
                        const maxLinks = 5;
                        const startPage = Math.max(1, currentPage - Math.floor(maxLinks / 2));
                        const endPage = Math.min(totalPages, startPage + maxLinks - 1);
                        for (let page = startPage; page <= endPage; page++) {
                            const active = page === currentPage ? "style='font-weight:bold;'" : '';
                            paginationHtml += `<a href='#' onclick='loadPatients("${search}", ${page})' ${active}>${page}</a> `;
                        }
                        if (currentPage < totalPages) {
                            paginationHtml += `<a href='#' onclick='loadPatients("${search}", ${currentPage + 1})'>Next</a>`;
                        }
                        $('#pagination').html(paginationHtml);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', { status, error, responseText: xhr.responseText });
                alert('Failed to load patients.');
            }
        });
    }

    // Search input
    $('#searchPatient').on('input', function() {
        const search = $(this).val().trim();
        if (search.length >= 1) {
            $.ajax({
                url: 'fetch_patients.php',
                method: 'GET',
                data: { search: search },
                success: function(response) {
                    $('#searchResults').html(response);
                    $('#patientTableContainer').hide();
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', { status, error, responseText: xhr.responseText });
                    alert('Failed to load patients.');
                }
            });
        } else {
            $('#searchResults').html('');
            $('#patientTableContainer').show();
            loadPatients();
        }
    });

    // Handle search result click
    $(document).on('click', '.search-result-item', function() {
        const search = $('#searchPatient').val().trim();
        $('#searchResults').html('');
        $('#patientTableContainer').show();
        loadPatients(search, 1);
    });

    // Clear search
    window.clearSearch = function() {
        $('#searchPatient').val('');
        $('#searchResults').html('');
        $('#patientTableContainer').show();
        loadPatients();
    };

    // Initial load
    loadPatients();

    // Hide message after 3 seconds
    setTimeout(function() {
        const messageContainer = $('#message-container');
        if (messageContainer.length) {
            messageContainer.fadeOut();
        }
    }, 3000);
});
</script>