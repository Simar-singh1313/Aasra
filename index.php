<?php
session_start();
$conn = mysqli_connect("localhost", "root", '', "aasra");

require 'vendor/autoload.php'; // For PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    // Save form data along with file
    if (isset($_POST['save_form'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $fname = mysqli_real_escape_string($conn, $_POST['fname']);
        $mobile_no = mysqli_real_escape_string($conn, $_POST['mobile_no']);
        $date_of_joining = $_POST['date_of_joining'];

        // File upload
        $file_name = '';
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $file_name = $_FILES['file']['name'];
            $file_tmp = $_FILES['file']['tmp_name'];
            $file_path = 'uploads/' . $file_name; // specify the upload directory

            // Move file to the uploads directory
            if (!move_uploaded_file($file_tmp, $file_path)) {
                $_SESSION['status'] = "Error uploading file!";
                header("Location: index.php");
                exit();
            }
        }

        // Insert form data and file name into the database
        $query = "INSERT INTO baghat (name, fname, mobile_no, date_of_joining, file_name, status) 
                  VALUES ('$name', '$fname', '$mobile_no', '$date_of_joining', '$file_name', 'active')";
        if (mysqli_query($conn, $query)) {
            $_SESSION['status'] = "Record saved successfully!";
        } else {
            $_SESSION['status'] = "Error saving record!";
        }
        header("Location: index.php");
        exit();
    }

    // Edit record
    if (isset($_POST['edit_baghat'])) {
        $baghat_id = $_POST['baghat_id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $fname = mysqli_real_escape_string($conn, $_POST['fname']);
        $mobile_no = mysqli_real_escape_string($conn, $_POST['mobile_no']);
        $date_of_joining = $_POST['date_of_joining'];
        $status = $_POST['status']; // status can be 'active' or 'inactive'
        $left_date = $status === 'inactive' ? $_POST['left_date'] : NULL; // only set left date if inactive

        // File upload (if provided)
        $file_name = $_FILES['file']['name'] ? $_FILES['file']['name'] : $_POST['existing_file'];
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $file_tmp = $_FILES['file']['tmp_name'];
            $file_path = 'uploads/' . $file_name;

            if (!move_uploaded_file($file_tmp, $file_path)) {
                $_SESSION['status'] = "Error uploading file!";
                header("Location: index.php");
                exit();
            }
        }

        $query = "UPDATE baghat SET name='$name', fname='$fname', mobile_no='$mobile_no', 
                  date_of_joining='$date_of_joining', file_name='$file_name', status='$status', 
                  left_date='$left_date' WHERE id='$baghat_id'";

        if (mysqli_query($conn, $query)) {
            $_SESSION['status'] = "Record updated successfully!";
        } else {
            $_SESSION['status'] = "Error updating record!";
        }
        header("Location: index.php");
        exit();
    }

    // Delete record
    if (isset($_POST['delete_baghat'])) {
        $baghat_id = $_POST['baghat_id'];
        $query = "DELETE FROM baghat WHERE id='$baghat_id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['status'] = "Record deleted successfully!";
        } else {
            $_SESSION['status'] = "Error deleting record!";
        }
        header("Location: index.php");
        exit();
    }
// Export to Excel with filter
if (isset($_POST['export_excel'])) {
    $status_filter = $_POST['status_filter']; // Get selected status filter
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header row
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'Father Name');
    $sheet->setCellValue('D1', 'Mobile No');
    $sheet->setCellValue('E1', 'Date of Joining');
    $sheet->setCellValue('F1', 'Left Date');
    $sheet->setCellValue('G1', 'Status');
    $sheet->setCellValue('H1', 'File Name');

    // Fetch data from database with status filter
    $query = "SELECT * FROM baghat";
    if ($status_filter && $status_filter !== 'both') {
        $query .= " WHERE status = '$status_filter'";
    }

    $result = mysqli_query($conn, $query);
    if (!$result) {
        die('Error executing query: ' . mysqli_error($conn));
    }

    $rowCount = 2; // Start from the second row
    while ($row = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue('A' . $rowCount, $row['id']);
        $sheet->setCellValue('B' . $rowCount, $row['name']);
        $sheet->setCellValue('C' . $rowCount, $row['fname']);
        $sheet->setCellValue('D' . $rowCount, $row['mobile_no']);
        $sheet->setCellValue('E' . $rowCount, $row['date_of_joining']);
        $sheet->setCellValue('F' . $rowCount, $row['left_date']);
        $sheet->setCellValue('G' . $rowCount, $row['status']);
        $sheet->setCellValue('H' . $rowCount, $row['file_name']);
        $rowCount++;
    }

    // Create the Excel file in memory
    $writer = new Xlsx($spreadsheet);

    // Clear any output buffer
    if (ob_get_contents()) {
        ob_end_clean();
    }

    // Set proper headers for Excel file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Baghat_Data_' . date('Y-m-d_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Save the file to output
    $writer->save('php://output');
    exit();

}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ni Aasre Da Aasra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <style>
        /* Custom Dropdown Styling */
        .custom-dropdown {
            width: 150px; /* Decrease the width of the dropdown */
            font-size: 0.9rem; /* Decrease font size */
        }

        /* Align the dropdown to the left */
        .custom-dropdown-container {
            text-align: left; /* Left alignment for the dropdown */
            display: inline-block;
            margin-right: 30px; /* Space for search on the right */
        }

        /* Custom search box styling */
        .search-container {
            display: flex;
            margin-left: 900px;
        }
    </style>
</head>
<body>

<!-- Baghat Add / Edit Modal -->
<div class="modal fade" id="Baghat" tabindex="-1" aria-labelledby="Baghat" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="BaghatLabel">Baghat Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter Name" required>
                    </div>
                    <div class="form-group">
                        <label for="fname">Father Name</label>
                        <input type="text" name="fname" id="fname" class="form-control" placeholder="Father Name" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile_no">Mobile Number</label>
                        <input type="text" name="mobile_no" id="mobile_no" class="form-control" placeholder="Mobile Number" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_joining">Date of Joining</label>
                        <input type="date" name="date_of_joining" id="date_of_joining" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="file">Upload File</label>
                        <input type="file" name="file" id="file" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" id="left_date_group" style="display: none;">
                        <label for="left_date">Date of Leaving</label>
                        <input type="date" name="left_date" id="left_date" class="form-control">
                    </div>
                    <input type="hidden" name="baghat_id" id="baghat_id">
                    <input type="hidden" name="existing_file" id="existing_file">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_form" id="save_btn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Baghat Modal -->
<div class="modal fade" id="ViewBaghat" tabindex="-1" aria-labelledby="ViewBaghatLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ViewBaghatLabel">View Baghat Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="view_name">Name</label>
                    <input type="text" id="view_name" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label for="view_fname">Father Name</label>
                    <input type="text" id="view_fname" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label for="view_mobile_no">Mobile Number</label>
                    <input type="text" id="view_mobile_no" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label for="view_date_of_joining">Date of Joining</label>
                    <input type="date" id="view_date_of_joining" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label for="view_file">Uploaded File</label>
                    <input type="text" id="view_file" class="form-control" disabled>
                </div>
                <button id="downloadFile" class="btn btn-success">Download File</button>
                <button id="downloadPdf" class="btn btn-info">Download as PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <?php
                if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
                    echo "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                        <strong></strong> {$_SESSION['status']}
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
                    unset($_SESSION['status']);
                }
                ?>
                <div class="card-header">
                    <h1>Ni Aasre Da Aasra
                        <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#Baghat">
                            New Admission
                        </button>
                    </h1>
                </div>
                <div class="card-body">
                    <!-- Dropdown and Search -->
                    <div class="d-flex align-items-center">
                        <!-- Status Dropdown -->
                        <div class="form-group mb-3 custom-dropdown-container">
                            <label for="filter_status">Status</label>
                            <select id="filter_status" class="form-control custom-dropdown">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                         
                        <!-- Search Box -->
                        <div class="search-container">
                            <label for="search_input"></label>
                            <input type="text" id="search_input" class="form-control" placeholder="Search...">
                        </div>
                    </div>
                       <!-- Export Button -->
                       <form action="index.php" method="POST" class="d-inline float-end">
                            <button type="submit" name="export_excel" class="btn btn-success">Export to Excel</button>
                        </form>
                    </div>
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">#ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Father Name</th>
                            <th scope="col">Mobile No</th>
                            <th scope="col">Date of Joining</th>
                            <th scope="col">left_date</th>
                            <th scope="col">Status</th>
                            <th scope="col">View</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $query = "SELECT * FROM baghat";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr data-status='{$row['status']}'>
                                <td>{$row['id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['fname']}</td>
                                <td>{$row['mobile_no']}</td>
                                <td>{$row['date_of_joining']}</td>
                                <td>{$row['left_date']}</td>
                                <td>{$row['status']}</td>
                                <td>
                                    <button class='btn btn-success view_btn' data-id='{$row['id']}' data-name='{$row['name']}' data-fname='{$row['fname']}' data-mobile_no='{$row['mobile_no']}' data-date_of_joining='{$row['date_of_joining']}' data-file='{$row['file_name']}'>View</button>
                                </td>
                                <td>
                                    <button class='btn btn-info edit_btn' data-id='{$row['id']}' data-name='{$row['name']}' data-fname='{$row['fname']}' data-mobile_no='{$row['mobile_no']}' data-date_of_joining='{$row['date_of_joining']}' data-status='{$row['status']}' data-file='{$row['file_name']}'>Edit</button>
                                    <button class='btn btn-danger delete_btn' data-id='{$row['id']}'>Delete</button>
                                </td>
                            </tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>

<script>
    $(document).ready(function () {
        // Toggle left date input based on status
        $('#status').change(function() {
            if ($(this).val() === 'inactive') {
                $('#left_date_group').show();
            } else {
                $('#left_date_group').hide();
            }
        });

        // Filter records based on status
        $('#filter_status').change(function () {
            var status = $(this).val();
            $('tbody tr').each(function () {
                var rowStatus = $(this).data('status');
                if (status === '' || rowStatus === status) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Search filter for table
        $('#search_input').on('input', function () {
            var searchText = $(this).val().toLowerCase();
            $('table tbody tr').each(function () {
                var rowText = $(this).text().toLowerCase();
                if (rowText.indexOf(searchText) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // View Button click event
        $('.view_btn').click(function () {
            var name = $(this).data('name');
            var fname = $(this).data('fname');
            var mobile_no = $(this).data('mobile_no');
            var date_of_joining = $(this).data('date_of_joining');
            var file_name = $(this).data('file');

            // Populate the modal fields
            $('#view_name').val(name);
            $('#view_fname').val(fname);
            $('#view_mobile_no').val(mobile_no);
            $('#view_date_of_joining').val(date_of_joining);
            $('#view_file').val(file_name);

            $('#ViewBaghat').modal('show');
        });

        // Edit Button click event
        $('.edit_btn').click(function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var fname = $(this).data('fname');
            var mobile_no = $(this).data('mobile_no');
            var date_of_joining = $(this).data('date_of_joining');
            var status = $(this).data('status');
            var file_name = $(this).data('file');

            // Populate the modal fields for editing
            $('#name').val(name);
            $('#fname').val(fname);
            $('#mobile_no').val(mobile_no);
            $('#date_of_joining').val(date_of_joining);
            $('#status').val(status);
            $('#existing_file').val(file_name);
            if (status === 'inactive') {
                $('#left_date_group').show();
            } else {
                $('#left_date_group').hide();
            }

            // Change form action and button name for editing
            $('#Baghat form').attr('action', 'index.php');
            $('#save_btn').attr('name', 'edit_baghat');
            $('#save_btn').val('Update');
            $('#baghat_id').val(id);

            $('#Baghat').modal('show');
        });

        // Delete Button click event
        $('.delete_btn').click(function () {
            var baghat_id = $(this).data('id');
            var confirmation = confirm("Are you sure you want to delete this record?");
            if (confirmation) {
                var form = $('<form>', {
                    action: 'index.php',
                    method: 'POST'
                }).append($('<input>', {
                    type: 'hidden',
                    name: 'baghat_id',
                    value: baghat_id
                })).append($('<input>', {
                    type: 'hidden',
                    name: 'delete_baghat',
                    value: true
                }));
                $('body').append(form);
                form.submit();
            }
        });

        // PDF Download functionality
        $('#downloadPdf').click(function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.setFont("helvetica", "bold");
            doc.setFontSize(18);
            doc.text("Ni Aasre Da Aasra", 105, 10, { align: "center" });

            doc.autoTable({
                startY: 20,
                head: [['Name', 'Father Name', 'Mobile No', 'Date of Joining', 'Uploaded File']],
                body: [
                    [
                        $('#view_name').val(),
                        $('#view_fname').val(),
                        $('#view_mobile_no').val(),
                        $('#view_date_of_joining').val(),
                        $('#view_file').val(),
                    ],
                ]
            });

            doc.save('baghat_data.pdf');
        });

        // Download file functionality
        $('#downloadFile').click(function () {
            var fileName = $('#view_file').val();
            if (fileName) {
                window.location.href = 'download.php?file=' + encodeURIComponent(fileName);
            } else {
                alert("No file available for download.");
            }
        });
    });
</script>

</body>
</html>
