<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            padding: 20px 0;
            margin: 0;
            background-color: #34495e;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .sidebar .active {
            background-color: #3498db;
        }

        .dropdown {
            display: none;
            background-color: #34495e;
        }

        .dropdown a {
            padding-left: 40px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .welcome-section {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #2980b9;
        }

        .table-container {
            margin: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: auto;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        .table-container th,
        .table-container td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table-container th {
            background-color: #2c3e50;
            color: white;
        }

        .table-container tr:hover {
            background-color: #f5f5f5;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-disabled {
            background-color: #ffebee;
            color: #c62828;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .enable-btn {
            background-color: #4caf50;
            color: white;
        }

        .enable-btn:hover {
            background-color: #45a049;
        }

        .disable-btn {
            background-color: #f44336;
            color: white;
        }

        .disable-btn:hover {
            background-color: #d32f2f;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: #f9f9f9;
        }
        
        .info-table th, .info-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .info-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 30%;
        }

        /* Semester Details Styling */
        .semester-details-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            overflow: hidden;
            border: 2px solid #e0e0e0;
        }

        .semester-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 25px;
            border-bottom: 3px solid #2980b9;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .semester-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #3498db, #2ecc71);
        }

        .semester-header h3 {
            margin: 0;
            font-size: 1.8em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 1px;
        }

        .courses-container {
            padding: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 30px;
            background: #f8f9fa;
        }

        .course-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            position: relative;
        }

        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #3498db, #2ecc71);
            border-radius: 4px 0 0 4px;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .course-header {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            font-size: 1.2em;
            border-bottom: 2px solid #2980b9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .course-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #3498db, #2ecc71);
        }

        .course-body {
            padding: 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 2px solid #f0f0f0;
            transition: all 0.3s ease;
            position: relative;
        }

        .detail-row:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
            border-left: 4px solid #3498db;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            color: #2c3e50;
            font-weight: 600;
            flex: 1;
            position: relative;
            padding-left: 20px;
        }

        .detail-row .label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background-color: #3498db;
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .detail-row .value {
            color: #34495e;
            flex: 2;
            text-align: right;
            font-weight: 500;
            padding-left: 20px;
            border-left: 1px dashed #e0e0e0;
        }

        .duration {
            margin: 20px;
            background: linear-gradient(to right, #f1f8ff, #e3f2fd);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #bbdefb;
            position: relative;
            overflow: hidden;
        }

        .duration::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #1565c0, #2196f3);
        }

        .duration .label {
            color: #1565c0;
            font-weight: bold;
        }

        .duration .value {
            color: #0d47a1;
            font-weight: 600;
        }

        /* Loading and Error States */
        .loading {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 1.1em;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px;
            border: 2px dashed #ccc;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, transparent, #3498db, transparent);
            animation: loading 2s infinite;
        }

        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .error-message {
            background: linear-gradient(to right, #fee, #fff);
            color: #c00;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px;
            border: 2px solid #ffcdd2;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .error-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #f44336, #ff5252);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .courses-container {
                grid-template-columns: 1fr;
                padding: 15px;
            }

            .detail-row {
                flex-direction: column;
                padding: 15px;
            }

            .detail-row .value {
                text-align: left;
                margin-top: 8px;
                padding-left: 20px;
                border-left: none;
                border-top: 1px dashed #e0e0e0;
                padding-top: 8px;
            }

            .semester-header h3 {
                font-size: 1.4em;
            }
        }

        .search-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-option {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .search-option h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        
        .search-input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .search-results-table th,
        .search-results-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .search-results-table th {
            background-color: #2c3e50;
            color: white;
        }
        
        .search-results-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .search-results-table tr:hover {
            background-color: #e9ecef;
        }
        
        #search-by-name-btn,
        #search-by-code-btn {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        #search-by-name-btn:hover,
        #search-by-code-btn:hover {
            background-color: #1a252f;
        }

        .status-enabled {
            color: green;
            font-weight: bold;
        }
        
        .status-disabled {
            color: red;
            font-weight: bold;
        }
        
        .enable-btn, .disable-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .enable-btn {
            background-color: #28a745;
            color: white;
        }
        
        .disable-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .enable-btn:hover {
            background-color: #218838;
        }
        
        .disable-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>
    <div class="header" style="background-color: #2c3e50; padding: 15px; width: 100%; position: fixed; top: 0; z-index: 1000;">
        <h1 style="float: left; margin: 0; padding: 0; font-size: 24px; font-weight: bold; color: white; display: block;">ASSIGNXPERT</h1>
        <a href="logout.php" style="float: right; margin: 5px 15px; text-decoration: none; color: white;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <div style="clear: both;"></div>
    </div>

    <!-- Add margin to the sidebar to account for the fixed header -->
    <div class="sidebar" style="margin-top: 60px; background-color: #2c3e50;">
        <h2>Admin Dashboard</h2>
        <a href="admin_dashboard.php" id="home-link" class="active"><i class="fas fa-home"></i> Home</a>
        <a href="#" id="department-link"><i class="fas fa-building"></i> DEPARTMENT</a>
        <div class="dropdown" id="department-dropdown">
            <a href="#" id="add-department"><i class="fas fa-plus"></i> Add Department</a>
        </div>
        <a href="#" id="course-link"><i class="fas fa-graduation-cap"></i> COURSE</a>
        <div class="dropdown" id="course-dropdown">
            <a href="#" id="add-course"><i class="fas fa-plus"></i> Add Course</a>
            <a href="#" id="view-courses"><i class="fas fa-list"></i> View Courses</a>
        </div>
        <a href="#" id="semester-link"><i class="fas fa-calendar-alt"></i> SEMESTER</a>
        <div class="dropdown" id="semester-dropdown">
            <a href="#" id="add-semester"><i class="fas fa-plus"></i> Add Semester</a>
            <a href="#" id="view-semesters"><i class="fas fa-list"></i> View Semesters</a>
        </div>
        <a href="#" id="subject-link"><i class="fas fa-book"></i> SUBJECT</a>
        <div class="dropdown" id="subject-dropdown">
            <a href="#" id="add-subject"><i class="fas fa-plus"></i> Add Subject</a>
            <a href="#" id="view-subjects"><i class="fas fa-list"></i> View Subjects</a>
        </div>
        <a href="#" id="teacher-link"><i class="fas fa-chalkboard-teacher"></i> TEACHER</a>
        <div class="dropdown" id="teacher-dropdown">
            <a href="#" id="add-teacher"><i class="fas fa-plus"></i> Add Teacher</a>
        </div>
        <a href="#" id="hod-link"><i class="fas fa-user-tie"></i> HOD</a>
        <div class="dropdown" id="hod-dropdown">
            <a href="#" id="add-hod"><i class="fas fa-plus"></i> Add HOD</a>
        </div>
    </div>

    <!-- Add margin to the main content to account for the fixed header -->
    <div class="main-content" style="margin-top: 60px;">
        <div class="welcome-section">
            <h2>Welcome to Admin Dashboard</h2>
            <div class="info-cards" style="display: flex; justify-content: space-between; margin-top: 20px;">
                <div class="info-card" onclick="showEnabledDepartments()" style="flex: 1; margin: 0 15px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; cursor: pointer;">
                    <i class="fas fa-building" style="font-size: 50px; color: #3498db; margin-bottom: 15px;"></i>
                    <h3 style="color: #2c3e50; margin-bottom: 10px;">Available Departments</h3>
                    <p style="font-size: 24px; color: #3498db; font-weight: bold;">5</p>
                </div>
                
                <div class="info-card" onclick="showEnabledCourses()" style="flex: 1; margin: 0 15px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; cursor: pointer;">
                    <i class="fas fa-graduation-cap" style="font-size: 50px; color: #3498db; margin-bottom: 15px;"></i>
                    <h3 style="color: #2c3e50; margin-bottom: 10px;">Available Courses</h3>
                    <p style="font-size: 24px; color: #3498db; font-weight: bold;">8</p>
                </div>
                
                <div class="info-card" onclick="showEnabledSubjects()" style="flex: 1; margin: 0 15px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; cursor: pointer;">
                    <i class="fas fa-book" style="font-size: 50px; color: #3498db; margin-bottom: 15px;"></i>
                    <h3 style="color: #2c3e50; margin-bottom: 10px;">Available Subjects</h3>
                    <p style="font-size: 24px; color: #3498db; font-weight: bold;">15</p>
                </div>
            </div>

            <!-- Updated table sections -->
            <div id="enabled-departments-table" style="display: none; margin-top: 20px;">
                <h3 style="margin-left: 20px;">Enabled Departments</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Department Code</th>
                            </tr>
                        </thead>
                        <tbody id="enabled-departments-list">
                            <!-- Departments will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="enabled-courses-table" style="display: none; margin-top: 20px;">
                <h3 style="margin-left: 20px;">Enabled Courses</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Course Code</th>
                                <th>Department</th>
                                <th>Number of Semesters</th>
                            </tr>
                        </thead>
                        <tbody id="enabled-courses-list">
                            <!-- Courses will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="enabled-subjects-table" style="display: none; margin-top: 20px;">
                <h3 style="margin-left: 20px;">Enabled Subjects</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Credit Points</th>
                            </tr>
                        </thead>
                        <tbody id="enabled-subjects-list">
                            <!-- Subjects will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="add-teacher-section" style="display: none;">
            <h2>Add Teacher</h2>
            <div class="form-container">
                <form id="add-teacher-form">
                    <div class="form-group">
                        <label for="teacher-email">Email</label>
                        <input type="email" id="teacher-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="teacher-department">Department</label>
                        <select id="teacher-department" name="department_id" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="teacher-designation">Designation</label>
                        <select id="teacher-designation" name="designation" required>
                            <option value="">Select Designation</option>
                            <option value="Junior Assistant Professor">Junior Assistant Professor</option>
                            <option value="Senior Assistant Professor">Senior Assistant Professor</option>
                            <option value="Associate Professor">Associate Professor</option>
                        </select>
                    </div>
                    <input type="hidden" name="role" value="teacher">
                    <button type="submit">Add Teacher</button>
                </form>
            </div>
        </div>

        <div id="add-hod-section" style="display: none;">
            <h2>Add HOD</h2>
            <div class="form-container">
                <form id="add-hod-form">
                    <div class="form-group">
                        <label for="hod-email">Email</label>
                        <input type="email" id="hod-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="hod-department">Department</label>
                        <select id="hod-department" name="department_id" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <input type="hidden" name="role" value="hod">
                    <button type="submit">Add HOD</button>
                </form>
            </div>
        </div>

        <div id="add-department-section" style="display: none;">
            <h2>Add Department</h2>
            <div class="form-container">
                <form id="add-department-form" action="add_department.php" method="POST">
                    <div class="form-group">
                        <label for="department-name">Department Name</label>
                        <input type="text" id="department-name" name="department-name" placeholder="Enter Department Name" required>
                    </div>

                    <div class="form-group">
                        <label for="department-code">Department Code</label>
                        <input type="text" id="department-code" name="department-code" placeholder="Enter Department Code (e.g., CS, MATH)" required>
                    </div>

                    <button type="submit">Add Department</button>
                </form>
            </div>

            <!-- Add the departments table below the form -->
            <div class="table-container" style="margin-top: 30px;">
                <h3>Existing Departments</h3>
                <table id="departments-table">
                    <thead>
                        <tr>
                            <th>Department Name</th>
                            <th>Department Code</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="departments-list">
                        <!-- Departments will be loaded here dynamically -->
                    </tbody>
                </table>
            </div>
        </div>

        <div id="add-course-section" style="display: none;">
            <h2>Add Course</h2>
            <div class="form-container">
                <form id="add-course-form" action="add_course.php" method="POST">
                    <div class="form-group">
                        <label for="department-select">Department</label>
                        <select id="department-select" name="department-id" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    
                    <!-- Add course type dropdown -->
                    <div class="form-group">
                        <label for="course-type">Course Type</label>
                        <select id="course-type" name="course-type" required>
                            <option value="">Select Course Type</option>
                            <option value="UG">UG</option>
                            <option value="PG">PG</option>
                            <option value="UG+PG">UG+PG</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course-name">Course Name</label>
                        <input type="text" id="course-name" name="course-name" required>
                    </div>
                    <div class="form-group">
                        <label for="course-code">Course Code</label>
                        <input type="text" id="course-code" name="course-code" required>
                    </div>
                    <div class="form-group">
                        <label for="num-semesters">Number of Semesters</label>
                        <input type="number" id="num-semesters" name="num-semesters" min="1" max="12" required>
                    </div>
                    <button type="submit">Add Course</button>
                </form>
            </div>
        </div>

        <div id="view-courses-section" style="display: none;">
            <h2>View Courses</h2>
            
            <div id="search-container" class="search-container" style="display: flex; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
                <div id="dept-search-option" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Department</h3>
                    <div class="form-group">
                        <select id="department-search" class="search-input">
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div id="department-courses-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Number of Semesters</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="name-search-option" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Name</h3>
                    <div class="form-group">
                        <input type="text" id="course-name-search" class="search-input" placeholder="Enter course name">
                        <button id="search-by-name-btn">Search</button>
                    </div>
                    <div id="course-name-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Department</th>
                                    <th>Number of Semesters</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="code-search-option" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Code</h3>
                    <div class="form-group">
                        <input type="text" id="course-code-search" class="search-input" placeholder="Enter course code">
                        <button id="search-by-code-btn">Search</button>
                    </div>
                    <div id="course-code-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Department</th>
                                    <th>Number of Semesters</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="back-to-search" style="display: none; margin-bottom: 20px;">
                <button class="btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                    ← Back to Search Options
                </button>
            </div>
        </div>

        <div id="add-subject-section" style="display: none;">
            <h2>Add Subject</h2>
            <div class="form-container">
                <form id="add-subject-form" action="add_subject.php" method="POST">
                    <div class="form-group">
                        <label for="department-select-subject">Department</label>
                        <select id="department-select-subject" name="department-id" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course-select-subject">Course</label>
                        <select id="course-select-subject" name="course-id" required disabled>
                            <option value="">Select Course</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="semester-select">Semester</label>
                        <select id="semester-select" name="semester_id" required disabled>
                            <option value="">Select Semester</option>
                        </select>
                    </div>

                    <!-- Add this after the semester select and before subject name -->
                    <div class="form-group">
                        <label for="subject-type">Subject Type:</label>
                        <select id="subject-type" name="subject-type" class="form-control" required>
                            <option value="">Select Subject Type</option>
                            <option value="theory">Theory</option>
                            <option value="lab">Lab</option>
                            <option value="elective">Elective</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject-name">Subject Name</label>
                        <input type="text" id="subject-name" name="subject-name" required>
                    </div>

                    <div class="form-group">
                        <label for="has-credits">Has Credit Points?</label>
                        <select id="has-credits" name="has-credits" required>
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="form-group" id="credit-points-group" style="display: none;">
                        <label for="credit-points">Credit Points</label>
                        <input type="number" id="credit-points" name="credit-points" min="1">
                    </div>

                    <button type="submit">Add Subject</button>
                </form>
            </div>
        </div>
        <div id="view-subjects-section" style="display: none;">
    <h2>View Subjects</h2>
    
<!-- Search interface -->
<div class="search-options" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
    <!-- Search by Course Name (existing) -->
    <div class="search-option" style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <h3 style="margin-top: 0; margin-bottom: 10px;">Search by Course Name</h3>
        <div class="form-group" style="display: flex; gap: 10px;">
            <input type="text" id="subject-course-search" placeholder="Enter course name" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <button id="search-subject-btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        </div>
    </div>
    
    <!-- Search by Course Code (existing) -->
    <div class="search-option" style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <h3 style="margin-top: 0; margin-bottom: 10px;">Search by Course Code</h3>
        <div class="form-group" style="display: flex; gap: 10px;">
            <input type="text" id="subject-course-code-search" placeholder="Enter course code" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <button id="search-subject-by-code-btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        </div>
    </div>
    
    <!-- Search by Semester Name (new) -->
    <div class="search-option" style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <h3 style="margin-top: 0; margin-bottom: 10px;">Search by Semester Name</h3>
        <div class="form-group" style="display: flex; gap: 10px;">
            <input type="text" id="subject-semester-search" placeholder="Enter semester name" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <button id="search-subject-by-semester-btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        </div>
    </div>
    
    <!-- Search by Subject Name (new) -->
    <div class="search-option" style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <h3 style="margin-top: 0; margin-bottom: 10px;">Search by Subject Name</h3>
        <div class="form-group" style="display: flex; gap: 10px;">
            <input type="text" id="subject-name-search" placeholder="Enter subject name" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <button id="search-subject-by-name-btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        </div>
    </div>
</div>
    
    <!-- Reset search button -->
    <div id="reset-subject-container" style="text-align: right; margin-bottom: 10px; display: none;">
        <button id="reset-subject-search" style="background-color: #6c757d; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer;">Show All Subjects</button>
    </div>
    
    <!-- Subjects table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Subject Name</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Credit Points</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="subjects-list">
                <!-- Subjects will be loaded here -->
            </tbody>
        </table>
    </div>
</div>
        <div id="add-semester-section" style="display: none;">
            <h2>Add Semester</h2>
            <div class="form-container">
                <form id="add-semester-form" action="add_semester.php" method="POST">
                    <!-- Add department select before course select -->
                    <div class="form-group">
                        <label for="department-select-semester">Department</label>
                        <select id="department-select-semester" name="department-id" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course-select">Course</label>
                        <select id="course-select" name="course-id" required disabled>
                            <option value="">Select Course</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester-name">Semester Name</label>
                        <input type="text" id="semester-name" name="semester-name" required>
                    </div>
                    <div class="form-group">
                        <label for="start-date">Start Date</label>
                        <input type="date" id="start-date" name="start-date" required>
                    </div>
                    <div class="form-group">
                        <label for="end-date">End Date</label>
                        <input type="date" id="end-date" name="end-date" required>
                    </div>
                    <button type="submit">Add Semester</button>
                </form>
            </div>
        </div>

        <div id="view-semesters-section" style="display: none;">
            <h2>View Semesters</h2>
            
            <div id="semester-search-container" class="search-container" style="display: flex; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
                <div id="dept-semester-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Department</h3>
                    <div class="form-group">
                        <select id="department-semester-search" class="search-input">
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div id="department-semesters-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Semester</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="course-name-semester-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Name</h3>
                    <div class="form-group">
                        <input type="text" id="course-name-semester-search-input" class="search-input" placeholder="Enter course name">
                        <button id="search-semester-by-course-name-btn">Search</button>
                    </div>
                    <div id="course-name-semesters-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Course Code</th>
                                    <th>Semester</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="course-code-semester-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Code</h3>
                    <div class="form-group">
                        <input type="text" id="course-code-semester-search-input" class="search-input" placeholder="Enter course code">
                        <button id="search-semester-by-course-code-btn">Search</button>
                    </div>
                    <div id="course-code-semesters-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Course Name</th>
                                    <th>Semester</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="semester-name-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Semester Name</h3>
                    <div class="form-group">
                        <input type="text" id="semester-name-search-input" class="search-input" placeholder="Enter semester name">
                        <button id="search-by-semester-name-btn">Search</button>
                    </div>
                    <div id="semester-name-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="back-to-semester-search" style="display: none; margin-bottom: 20px;">
                <button class="btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                    ← Back to Search Options
                </button>
            </div>
        </div>
    </div>

    <script>
        const homeLink = document.getElementById('home-link');
        const departmentLink = document.getElementById('department-link');
        const departmentDropdown = document.getElementById('department-dropdown');
        const courseLink = document.getElementById('course-link');
        const courseDropdown = document.getElementById('course-dropdown');
        const teacherLink = document.getElementById('teacher-link');
        const teacherDropdown = document.getElementById('teacher-dropdown');
        const hodLink = document.getElementById('hod-link');
        const hodDropdown = document.getElementById('hod-dropdown');
        const addTeacherSection = document.getElementById('add-teacher-section');
        const addHodSection = document.getElementById('add-hod-section');
        const welcomeSection = document.querySelector('.welcome-section');
        const addDepartmentSection = document.getElementById('add-department-section');
        const addCourseSection = document.getElementById('add-course-section');
        const viewCoursesSection = document.getElementById('view-courses-section');
        const addSubjectSection = document.getElementById('add-subject-section');
        const viewSubjectsSection = document.getElementById('view-subjects-section');
        const addSemesterSection = document.getElementById('add-semester-section');
        const viewSemestersSection = document.getElementById('view-semesters-section');

        // Home link click handler
        homeLink.addEventListener('click', () => {
            showSection(welcomeSection);
            setActiveLink(homeLink);
        });

        // Department link handlers
        departmentLink.addEventListener('click', () => {
            const isVisible = departmentDropdown.style.display === 'block';
            departmentDropdown.style.display = isVisible ? 'none' : 'block';
            courseDropdown.style.display = 'none';
            teacherDropdown.style.display = 'none';
            hodDropdown.style.display = 'none';
            setActiveLink(departmentLink);
        });

        document.getElementById('add-department').addEventListener('click', () => {
            showSection(addDepartmentSection);
            loadDepartments(); // Load departments when showing the section
            departmentDropdown.style.display = 'none';
            setActiveLink(departmentLink);
        });

        // Course link handlers
        courseLink.addEventListener('click', () => {
            const isVisible = courseDropdown.style.display === 'block';
            courseDropdown.style.display = isVisible ? 'none' : 'block';
            departmentDropdown.style.display = 'none';
            teacherDropdown.style.display = 'none';
            hodDropdown.style.display = 'none';
            setActiveLink(courseLink);
        });

        document.getElementById('add-course').addEventListener('click', () => {
            showSection(addCourseSection);
            loadActiveDepartments();
        });

        document.getElementById('view-courses').addEventListener('click', () => {
            showSection(viewCoursesSection);
            loadCourses();
            courseDropdown.style.display = 'none';
            setActiveLink(courseLink);
        });

        // Teacher link handlers
        teacherLink.addEventListener('click', () => {
            const isVisible = teacherDropdown.style.display === 'block';
            teacherDropdown.style.display = isVisible ? 'none' : 'block';
            hodDropdown.style.display = 'none';
            setActiveLink(teacherLink);
        });

        document.getElementById('add-teacher').addEventListener('click', () => {
            showSection(addTeacherSection);
            loadEnabledDepartmentsForStaff('teacher-department');
            teacherDropdown.style.display = 'none';
            setActiveLink(teacherLink);
        });

        // HOD link handlers
        hodLink.addEventListener('click', () => {
            const isVisible = hodDropdown.style.display === 'block';
            hodDropdown.style.display = isVisible ? 'none' : 'block';
            teacherDropdown.style.display = 'none';
            setActiveLink(hodLink);
        });

        document.getElementById('add-hod').addEventListener('click', () => {
            showSection(addHodSection);
            loadEnabledDepartmentsForStaff('hod-department');
            hodDropdown.style.display = 'none';
            setActiveLink(hodLink);
        });

        // Subject management
        const subjectLink = document.getElementById('subject-link');
        const subjectDropdown = document.getElementById('subject-dropdown');

        // Show/hide credit points input based on selection
        document.getElementById('has-credits').addEventListener('change', function() {
            const creditPointsGroup = document.getElementById('credit-points-group');
            const creditPointsInput = document.getElementById('credit-points');
            
            if (this.value === '1') {
                creditPointsGroup.style.display = 'block';
                creditPointsInput.required = true;
            } else {
                creditPointsGroup.style.display = 'none';
                creditPointsInput.required = false;
                creditPointsInput.value = '';
            }
        });

        // Subject link handlers
        subjectLink.addEventListener('click', () => {
            const isVisible = subjectDropdown.style.display === 'block';
            subjectDropdown.style.display = isVisible ? 'none' : 'block';
            setActiveLink(subjectLink);
        });

        document.getElementById('add-subject').addEventListener('click', () => {
            console.log('Add subject clicked');
            showSection(addSubjectSection);
            loadActiveSemesters();
        });

        document.getElementById('view-subjects').addEventListener('click', () => {
            showSection(viewSubjectsSection);
            loadSubjects();
            subjectDropdown.style.display = 'none';
            setActiveLink(subjectLink);
        });

        // Semester link handlers
        document.getElementById('add-semester').addEventListener('click', () => {
            showSection(addSemesterSection);
            loadDepartmentsForSemester();
            document.getElementById('course-select').disabled = true;
            const semesterDropdown = document.getElementById('semester-dropdown');
            if (semesterDropdown) {
                semesterDropdown.style.display = 'none';
            }
            setActiveLink(document.getElementById('semester-link'));
        });

        document.getElementById('view-semesters').addEventListener('click', () => {
            showSection(viewSemestersSection);
            loadSemesters();
            courseDropdown.style.display = 'none';
            setActiveLink(courseLink);
        });

        function showSection(section) {
            const sections = [
                welcomeSection,
                addTeacherSection,
                addHodSection,
                addDepartmentSection,
                addCourseSection,
                viewCoursesSection,
                addSubjectSection,
                viewSubjectsSection,
                addSemesterSection,
                viewSemestersSection
            ];
            
            sections.forEach(sec => sec.style.display = 'none');
            section.style.display = 'block';
        }

        // Function to set active link
        function setActiveLink(activeLink) {
            const links = document.querySelectorAll('.sidebar > a');
            links.forEach(link => link.classList.remove('active'));
            activeLink.classList.add('active');
        }

        // Function to handle form submission
        function handleFormSubmission(formId, userType) {
            document.getElementById(formId).addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;
                submitButton.textContent = 'Adding...';
                submitButton.disabled = true;

                const formData = new FormData(this);

                fetch('add_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        let message = data.message;
                        if (data.password) {
                            message += '\n\nTemporary Password: ' + data.password;
                        }
                        alert(message);
                        this.reset();
                    } else {
                        alert(data.message || `Error adding ${userType}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`Error adding ${userType}. Please try again.`);
                })
                .finally(() => {
                    submitButton.textContent = originalButtonText;
                    submitButton.disabled = false;
                });
            });
        }

        // Initialize form handlers
        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmission('add-teacher-form', 'teacher');
            handleFormSubmission('add-hod-form', 'hod');
        });

        // Load departments
        function loadDepartments() {
            fetch('get_departments.php')
                .then(response => response.json())
                .then(result => {
                    const tbody = document.getElementById('departments-list');
                    tbody.innerHTML = '';
                    
                    if (result.success && result.data) {
                        result.data.forEach(dept => {
                            const row = `
                                <tr data-department-id="${dept.id}">
                                    <td>${dept.name}</td>
                                    <td>${dept.code}</td>
                                    <td>
                                        <span class="status-badge ${dept.is_disabled ? 'status-disabled' : 'status-active'}">
                                            ${dept.is_disabled ? 'Disabled' : 'Enabled'}
                                        </span>
                                    </td>
                                    <td>
                                        <button 
                                            class="action-btn ${dept.is_disabled ? 'enable-btn' : 'disable-btn'}"
                                            onclick="toggleDepartmentStatus(${dept.id}, ${dept.is_disabled})"
                                        >
                                            ${dept.is_disabled ? 'Enable' : 'Disable'}
                                        </button>
                                    </td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No departments found</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const tbody = document.getElementById('departments-list');
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Error loading departments</td></tr>';
                });
        }

        // Toggle department status
        function toggleDepartmentStatus(id, currentStatus) {
            fetch('toggle_department.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: id,
                    status: !currentStatus
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Find the row and update it immediately
                    const row = document.querySelector(`tr[data-department-id="${id}"]`);
                    if (row) {
                        const statusBadge = row.querySelector('.status-badge');
                        const actionButton = row.querySelector('.action-btn');
                        const newStatus = !currentStatus;

                        // Update status badge
                        statusBadge.className = `status-badge ${newStatus ? 'status-disabled' : 'status-active'}`;
                        statusBadge.textContent = newStatus ? 'Disabled' : 'Enabled';

                        // Update action button
                        actionButton.className = `action-btn ${newStatus ? 'enable-btn' : 'disable-btn'}`;
                        actionButton.textContent = newStatus ? 'Enable' : 'Disable';
                        actionButton.onclick = () => toggleDepartmentStatus(id, newStatus);
                    }
                } else {
                    alert('Error updating department status');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Add department form submission
        document.getElementById('add-department-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('add_department.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Department added successfully!');
                    this.reset();
                    loadDepartments(); // Reload the departments table
                } else {
                    alert(result.message || 'Error adding department');
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Load courses
        function loadCourses() {
            fetch('get_courses.php')
                .then(response => response.json())
                .then(courses => {
                    const tbody = document.getElementById('courses-list');
                    tbody.innerHTML = '';
                    
                    courses.forEach(course => {
                        const row = `
                            <tr data-course-id="${course.id}">
                                <td>${course.name}</td>
                                <td>${course.code}</td>
                                <td>${course.num_semesters}</td>
                                <td>
                                    <span class="status-badge ${course.is_disabled ? 'status-disabled' : 'status-active'}">
                                        ${course.is_disabled ? 'Disabled' : 'Enabled'}
                                    </span>
                                </td>
                                <td>
                                    <button 
                                        class="action-btn ${course.is_disabled ? 'enable-btn' : 'disable-btn'}"
                                        onclick="toggleCourseStatus(${course.id}, ${course.is_disabled})"
                                    >
                                        ${course.is_disabled ? 'Enable' : 'Disable'}
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Toggle course status
        function toggleCourseStatus(id, currentStatus) {
            fetch('toggle_course.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    course_id: id,
                    status: !currentStatus
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const row = document.querySelector(`tr[data-course-id="${id}"]`);
                    if (row) {
                        const statusBadge = row.querySelector('.status-badge');
                        const actionButton = row.querySelector('.action-btn');
                        const newStatus = !currentStatus;

                        statusBadge.className = `status-badge ${newStatus ? 'status-disabled' : 'status-active'}`;
                        statusBadge.textContent = newStatus ? 'Disabled' : 'Enabled';

                        actionButton.className = `action-btn ${newStatus ? 'enable-btn' : 'disable-btn'}`;
                        actionButton.textContent = newStatus ? 'Enable' : 'Disable';
                        actionButton.onclick = () => toggleCourseStatus(id, newStatus);
                    }
                } else {
                    alert('Error updating course status');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Course form submission handler with validation
        document.getElementById('add-course-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Get form values
            const departmentId = document.getElementById('department-select').value;
            const courseType = document.getElementById('course-type').value;
            const numSemesters = parseInt(document.getElementById('num-semesters').value);
            
            // Validate department selection
            if (!departmentId) {
                alert('Please select a department');
                return;
            }

            // Validate course type selection
            if (!courseType) {
                alert('Please select a course type');
                return;
            }

            // Validate number of semesters based on course type
            let isValid = true;
            let message = '';
            
            switch(courseType) {
                case 'UG':
                    if (numSemesters !== 6 && numSemesters !== 8) {
                        isValid = false;
                        message = 'UG courses must have 6 or 8 semesters';
                    }
                    break;
                    
                case 'PG':
                    if (numSemesters !== 4) {
                        isValid = false;
                        message = 'PG courses must have 4 semesters';
                    }
                    break;
                    
                case 'UG+PG':
                    if (numSemesters !== 10 && numSemesters !== 12) {
                        isValid = false;
                        message = 'UG+PG courses must have 10 or 12 semesters';
                    }
                    break;
            }
            
            if (!isValid) {
                alert(message);
                return;
            }

            const formData = new FormData(this);
            
            // Log the form data for debugging
            console.log('Form data:', {
                departmentId,
                courseType,
                numSemesters,
                allData: Object.fromEntries(formData)
            });

            // Submit the form
            fetch('add_course.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Course added successfully!');
                    this.reset();
                    // Reload the courses list if viewing courses section
                    if (document.getElementById('view-courses-section').style.display === 'block') {
                        loadCourses();
                    }
                } else {
                    alert(result.message || 'Error adding course');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding course. Please try again.');
            });
        });

        // Load subjects
        function loadSubjects() {
            const subjectsList = document.getElementById('subjects-list');
            if (!subjectsList) return;
            
            subjectsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Loading subjects...</td></tr>';
            
            fetch('get_subjects.php')
                .then(response => response.json())
                .then(subjects => {
                    displaySubjects(subjects);
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    subjectsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Error loading subjects</td></tr>';
                });
        }

        // Function to display subjects in the table
        function displaySubjects(subjects) {
            const subjectsList = document.getElementById('subjects-list');
            if (!subjectsList) return;
            
            subjectsList.innerHTML = '';
            
            if (!Array.isArray(subjects) || subjects.length === 0) {
                subjectsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">No subjects found</td></tr>';
                return;
            }
            
            subjects.forEach(subject => {
                const row = document.createElement('tr');
                row.setAttribute('data-subject-id', subject.id);
                
                const statusClass = subject.is_disabled == 1 ? 'status-disabled' : 'status-active';
                const statusText = subject.is_disabled == 1 ? 'Disabled' : 'Enabled';
                const actionBtn = subject.is_disabled == 1 ? 
                    `<button class="enable-btn" onclick="toggleSubjectStatus(${subject.id}, true)">Enable</button>` : 
                    `<button class="disable-btn" onclick="toggleSubjectStatus(${subject.id}, false)">Disable</button>`;
                
                row.innerHTML = `
                    <td>${subject.name}</td>
                    <td>${subject.course_name || 'N/A'}</td>
                    <td>${subject.semester_name || 'N/A'}</td>
                    <td>${subject.has_credits == 1 ? subject.credit_points : 'N/A'}</td>
                    <td><span class="${statusClass}">${statusText}</span></td>
                    <td>${actionBtn}</td>
                `;
                
                subjectsList.appendChild(row);
            });
        }

        // Function to search subjects by course name
        function searchSubjectsByCourse() {
            const courseName = document.getElementById('subject-course-search').value.trim();
            if (!courseName) {
                alert('Please enter a course name to search');
                return;
            }
            
            const subjectsList = document.getElementById('subjects-list');
            subjectsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Searching subjects...</td></tr>';
            
            fetch(`search_subjects.php?course_name=${encodeURIComponent(courseName)}`)
                .then(response => response.json())
                .then(subjects => {
                    displaySubjects(subjects);
                })
                .catch(error => {
                    console.error('Error searching subjects:', error);
                    subjectsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Error searching subjects</td></tr>';
                });
        }

        // Function to toggle subject status (enable/disable)
        function toggleSubjectStatus(subjectId, isCurrentlyDisabled) {
            console.log(`Toggling subject ${subjectId} status, currently disabled: ${isCurrentlyDisabled}`);
            
            const newStatus = isCurrentlyDisabled ? 0 : 1; // Toggle the status
            
            fetch('toggle_subject.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subject_id: subjectId,
                    status: newStatus
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Toggle result:', result);
                
                if (result.success) {
                    // Update the UI without reloading
                    const row = document.querySelector(`tr[data-subject-id="${subjectId}"]`);
                    if (row) {
                        const statusCell = row.querySelector('td:nth-child(6)');
                        const actionCell = row.querySelector('td:nth-child(7)');
                        
                        if (statusCell && actionCell) {
                            if (newStatus == 1) { // Disabled
                                statusCell.innerHTML = '<span class="status-badge status-disabled">Disabled</span>';
                                actionCell.innerHTML = `<button class="action-btn enable-btn" onclick="toggleSubjectStatus(${subjectId}, true)">Enable</button>`;
                            } else { // Enabled
                                statusCell.innerHTML = '<span class="status-badge status-active">Enabled</span>';
                                actionCell.innerHTML = `<button class="action-btn disable-btn" onclick="toggleSubjectStatus(${subjectId}, false)">Disable</button>`;
                            }
                        }
                    }
                    
                    alert(`Subject ${newStatus == 1 ? 'disabled' : 'enabled'} successfully!`);
                } else {
                    alert('Failed to update subject status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating subject status: ' + error.message);
            });
        }

        // Add event listeners when the document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // ... existing event listeners ...
            
            // View Subjects link
            const viewSubjectsLink = document.getElementById('view-subjects');
            if (viewSubjectsLink) {
                viewSubjectsLink.addEventListener('click', function() {
                    console.log('View Subjects clicked');
                    
                    const viewSubjectsSection = document.getElementById('view-subjects-section');
                    const subjectLink = document.getElementById('subject-link');
                    const subjectDropdown = document.getElementById('subject-dropdown');
                    
                    if (viewSubjectsSection) {
                        showSection(viewSubjectsSection);
                        
                        if (subjectLink) {
                            setActiveLink(subjectLink);
                        }
                        
                        if (subjectDropdown) {
                            subjectDropdown.style.display = 'block';
                        }
                        
                        loadSubjects();
                    } else {
                        console.error('view-subjects-section not found');
                    }
                });
            }
            
            // Search button event
            const searchSubjectBtn = document.getElementById('search-subject-btn');
            if (searchSubjectBtn) {
                searchSubjectBtn.addEventListener('click', searchSubjectsByCourse);
            }
            
            // Search on Enter key
            const subjectCourseSearch = document.getElementById('subject-course-search');
            if (subjectCourseSearch) {
                subjectCourseSearch.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchSubjectsByCourse();
                    }
                });
            }
            
            // Reset search button
            const resetSubjectSearch = document.getElementById('reset-subject-search');
            if (resetSubjectSearch) {
                resetSubjectSearch.addEventListener('click', function() {
                    document.getElementById('subject-course-search').value = '';
                    loadSubjects();
                });
            }
        });

        // Live validation for Department
        document.getElementById('department-name').addEventListener('input', function() {
            const departmentName = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!departmentName) {
                showError(errorDiv, 'Department name is required');
                return;
            }
            
            if (!/^[A-Za-z\s]+$/.test(departmentName)) {
                showError(errorDiv, 'Department name should only contain letters and spaces');
                return;
            }
            
            // Check if department exists
            fetch('check_department.php?name=' + encodeURIComponent(departmentName))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(errorDiv, 'Department already exists');
                    } else {
                        hideError(errorDiv);
                    }
                });
        });

        document.getElementById('department-code').addEventListener('input', function() {
            const code = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!code) {
                showError(errorDiv, 'Department code is required');
                return;
            }
            
            if (!/^[A-Z0-9]+$/.test(code)) {
                showError(errorDiv, 'Department code should only contain uppercase letters and numbers');
                return;
            }
            
            // Check if code exists
            fetch('check_department.php?code=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(errorDiv, 'Department code already exists');
                    } else {
                        hideError(errorDiv);
                    }
                });
        });

        // Live validation for Course
        document.getElementById('course-name').addEventListener('input', function() {
            const courseName = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!courseName) {
                showError(errorDiv, 'Course name is required');
                return;
            }
          // Replace it with this regex that only allows &, ., and () as special characters:
if (!/^[A-Za-z\s\&\.\(\)]+$/.test(courseName)) {
    showError(errorDiv, 'Course name can only contain letters, spaces, and the special characters & . ( )');
    return;
}
            
            // Check if course exists
            fetch('check_course.php?name=' + encodeURIComponent(courseName))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(errorDiv, 'Course already exists');
                    } else {
                        hideError(errorDiv);
                    }
                });
        });

        document.getElementById('course-code').addEventListener('input', function() {
            const code = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!code) {
                showError(errorDiv, 'Course code is required');
                return;
            }
            
            if (!/^[A-Z0-9]+$/.test(code)) {
                showError(errorDiv, 'Course code should only contain uppercase letters and numbers');
                return;
            }
            
            // Check if code exists
            fetch('check_course.php?code=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(errorDiv, 'Course code already exists');
                    } else {
                        hideError(errorDiv);
                    }
                });
        });

        // Live validation for Subject
        document.getElementById('subject-name').addEventListener('input', function() {
            const subjectName = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!subjectName) {
                showError(errorDiv, 'Subject name is required');
                return;
            }
            
            if (!/^[A-Za-z\s]+$/.test(subjectName)) {
                showError(errorDiv, 'Subject name should only contain letters and spaces');
                return;
            }
            
            // Check if subject exists
            fetch('check_subject.php?name=' + encodeURIComponent(subjectName))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(errorDiv, 'Subject already exists');
                    } else {
                        hideError(errorDiv);
                    }
                });
        });

        document.getElementById('credit-points').addEventListener('input', function() {
            const points = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (document.getElementById('has-credits').value === '1') {
                if (!points || points < 1) {
                    showError(errorDiv, 'Credit points must be greater than 0');
                } else if (points > 10) {
                    showError(errorDiv, 'Credit points cannot exceed 10');
                } else {
                    hideError(errorDiv);
                }
            }
        });

        // Utility functions for error handling
        function createErrorDiv(inputElement) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.color = 'red';
            errorDiv.style.fontSize = '12px';
            errorDiv.style.marginTop = '5px';
            inputElement.parentElement.appendChild(errorDiv);
            return errorDiv;
        }

        function showError(errorDiv, message) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.previousElementSibling.style.borderColor = 'red';
        }

        function hideError(errorDiv) {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
            errorDiv.previousElementSibling.style.borderColor = '#ddd';
        }

        // Form submission validation
        document.getElementById('add-department-form').addEventListener('submit', function(event) {
            const errorDivs = this.querySelectorAll('.error-message');
            for (let errorDiv of errorDivs) {
                if (errorDiv.style.display === 'block') {
                    event.preventDefault();
                    alert('Please fix the errors before submitting');
                    return;
                }
            }
        });

        document.getElementById('add-course-form').addEventListener('submit', function(event) {
            const errorDivs = this.querySelectorAll('.error-message');
            for (let errorDiv of errorDivs) {
                if (errorDiv.style.display === 'block') {
                    event.preventDefault();
                    alert('Please fix the errors before submitting');
                    return;
                }
            }
        });

        document.getElementById('add-subject-form').addEventListener('submit', function(event) {
            const errorDivs = this.querySelectorAll('.error-message');
            for (let errorDiv of errorDivs) {
                if (errorDiv.style.display === 'block') {
                    event.preventDefault();
                    alert('Please fix the errors before submitting');
                    return;
                }
            }
        });

        // Semester management
        document.getElementById('semester-link').addEventListener('click', () => {
            const semesterDropdown = document.getElementById('semester-dropdown');
            if (semesterDropdown) {
                const isVisible = semesterDropdown.style.display === 'block';
                semesterDropdown.style.display = isVisible ? 'none' : 'block';
            }
            setActiveLink(document.getElementById('semester-link'));
        });

        document.getElementById('add-semester').addEventListener('click', () => {
            showSection(addSemesterSection);
            loadDepartmentsForSemester();
            document.getElementById('course-select').disabled = true;
            const semesterDropdown = document.getElementById('semester-dropdown');
            if (semesterDropdown) {
                semesterDropdown.style.display = 'none';
            }
            setActiveLink(document.getElementById('semester-link'));
        });

        document.getElementById('view-semesters').addEventListener('click', () => {
            showSection(viewSemestersSection);
            loadSemesters();
        });

        // Load active courses for dropdown
        function loadActiveCourses() {
            fetch('get_active_courses.php')
                .then(response => response.json())
                .then(courses => {
                    const select = document.getElementById('course-select');
                    select.innerHTML = '<option value="">Select Course</option>';
                    courses.forEach(course => {
                        select.innerHTML += `<option value="${course.id}">${course.name}</option>`;
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Live validation for semester name
        document.getElementById('semester-name').addEventListener('input', function() {
            const semesterName = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!semesterName) {
                showError(errorDiv, 'Semester name is required');
                return;
            }
            
            if (!/^[A-Za-z\s]+[IVX]*$/.test(semesterName)) {
                showError(errorDiv, 'Semester name should contain only letters and Roman numerals');
                return;
            }
            
            hideError(errorDiv);
        });

        // Date validation
        document.getElementById('end-date').addEventListener('change', function() {
            const startDate = new Date(document.getElementById('start-date').value);
            const endDate = new Date(this.value);
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (endDate <= startDate) {
                showError(errorDiv, 'End date must be after start date');
            } else {
                hideError(errorDiv);
            }
        });

        // Add semester form submission
        document.getElementById('add-semester-form').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submission started');

            const formData = new FormData(this);

            // Debug log the form data
            console.log('Form data being sent:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            fetch('add_semester.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Raw response:', response);
                return response.json();
            })
            .then(data => {
                console.log('Processed response:', data);
                if (data.success) {
                    alert('Semester added successfully!');
                    this.reset();
                    // Reset course select
                    const courseSelect = document.getElementById('course-select');
                    courseSelect.disabled = true;
                    courseSelect.innerHTML = '<option value="">Select Course</option>';
                } else {
                    throw new Error(data.message || 'Failed to add semester');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            });
        });

        // Load semesters
        function loadSemesters() {
            fetch('get_semesters.php')
                .then(response => response.json())
                .then(semesters => {
                    const tbody = document.getElementById('semesters-list');
                    tbody.innerHTML = '';
                    
                    semesters.forEach(semester => {
                        const row = `
                            <tr data-semester-id="${semester.id}">
                                <td>${semester.course_name}</td>
                                <td>${semester.name}</td>
                                <td>${formatDate(semester.start_date)}</td>
                                <td>${formatDate(semester.end_date)}</td>
                                <td>
                                    <span class="status-badge ${semester.is_disabled ? 'status-disabled' : 'status-active'}">
                                        ${semester.is_disabled ? 'Disabled' : 'Enabled'}
                                    </span>
                                </td>
                                <td>
                                    <button 
                                        class="action-btn ${semester.is_disabled ? 'enable-btn' : 'disable-btn'}"
                                        onclick="toggleSemesterStatus(${semester.id}, ${semester.is_disabled})"
                                    >
                                        ${semester.is_disabled ? 'Enable' : 'Disable'}
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Toggle semester status
        function toggleSemesterStatus(id, currentStatus) {
            fetch('toggle_semester.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    semester_id: id,
                    status: !currentStatus
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const row = document.querySelector(`tr[data-semester-id="${id}"]`);
                    if (row) {
                        const statusBadge = row.querySelector('.status-badge');
                        const actionButton = row.querySelector('.action-btn');
                        const newStatus = !currentStatus;

                        // Update status badge
                        statusBadge.className = `status-badge ${newStatus ? 'status-disabled' : 'status-active'}`;
                        statusBadge.textContent = newStatus ? 'Disabled' : 'Enabled';

                        // Update action button
                        actionButton.className = `action-btn ${newStatus ? 'enable-btn' : 'disable-btn'}`;
                        actionButton.textContent = newStatus ? 'Enable' : 'Disable';
                        actionButton.onclick = () => toggleSemesterStatus(id, newStatus);
                    }
                } else {
                    alert('Error updating semester status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating semester status');
            });
        }

        // Utility function to format dates
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }

        // Add this to your existing JavaScript
        document.getElementById('add-course').addEventListener('click', () => {
            showSection(document.getElementById('add-course-section'));
            loadActiveDepartments();
        });

        function loadActiveDepartments() {
            fetch('get_active_departments.php')
                .then(response => response.json())
                .then(departments => {
                    const select = document.getElementById('department-select');
                    select.innerHTML = '<option value="">Select Department</option>';
                    departments.forEach(dept => {
                        select.innerHTML += `<option value="${dept.id}">${dept.name} (${dept.code})</option>`;
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Add live validation for course name
        document.getElementById('course-name').addEventListener('input', function() {
            const courseName = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!courseName) {
                showError(errorDiv, 'Course name is required');
                return;
            }
            
            if (!/^[A-Za-z\s]+$/.test(courseName)) {
                showError(errorDiv, 'Course name should only contain letters and spaces');
                return;
            }
            
            // Check if course exists
            fetch('check_course.php?name=' + encodeURIComponent(courseName))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(errorDiv, 'Course already exists');
                    } else {
                        hideError(errorDiv);
                    }
                });
        });

        // Add validation for course code
        document.getElementById('course-code').addEventListener('input', function() {
            const code = this.value;
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (!code) {
                showError(errorDiv, 'Course code is required');
                return;
            }
            
            if (!/^[A-Z0-9]+$/.test(code)) {
                showError(errorDiv, 'Course code should only contain uppercase letters and numbers');
                return;
            }
            
            // Check if code exists
            fetch('check_course.php?code=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        showError(errorDiv, 'Course code already exists');
                    } else {
                        hideError(errorDiv);
                    }
                });
        });

        // Add validation for number of semesters
        document.getElementById('num-semesters').addEventListener('input', function() {
            const numSemesters = parseInt(this.value);
            const errorDiv = this.parentElement.querySelector('.error-message') || createErrorDiv(this);
            
            if (isNaN(numSemesters) || numSemestersInput.value.trim() === '') {
    showError(errorDiv, 'Number of semesters is required');

            } else if (numSemesters > 12) {
                showError(errorDiv, 'Number of semesters cannot exceed 12');
            } else {
                hideError(errorDiv);
            }
        });

        // Add form submission validation
        document.getElementById('add-course-form').addEventListener('submit', function(event) {
            if (!document.getElementById('department-select').value) {
                event.preventDefault();
                alert('Please select a department');
                return;
            }

            const errorDivs = this.querySelectorAll('.error-message');
            for (let errorDiv of errorDivs) {
                if (errorDiv.style.display === 'block') {
                    event.preventDefault();
                    alert('Please fix the errors before submitting');
                    return;
                }
            }
        });

        // Function to load active semesters
        function loadActiveSemesters() {
            const semesterSelect = document.getElementById('semester-select');
            if (!semesterSelect) {
                console.error('Semester select element not found');
                return;
            }

            console.log('Loading semesters...');
            semesterSelect.innerHTML = '<option value="">Loading semesters...</option>';

            fetch('get_active_semesters.php')
                .then(response => response.json())
                .then(result => {
                    console.log('Semester data received:', result);
                    
                    semesterSelect.innerHTML = '<option value="">Select Semester</option>';
                    
                    if (result.success && result.data && result.data.length > 0) {
                        result.data.forEach(semester => {
                            const option = document.createElement('option');
                            option.value = semester.id;
                            option.textContent = semester.name;
                            semesterSelect.appendChild(option);
                        });
                        console.log('Semesters loaded successfully');
                    } else {
                        console.log('No semesters found');
                        semesterSelect.innerHTML = '<option value="">No semesters available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading semesters:', error);
                    semesterSelect.innerHTML = '<option value="">Error loading semesters</option>';
                });
        }

        // Make sure event listeners are properly attached when the document loads
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Document loaded');
            
            const semesterSelect = document.getElementById('semester-select');
            if (semesterSelect) {
                console.log('Semester select found, attaching event listener');
                semesterSelect.addEventListener('change', handleSemesterSelection);
            } else {
                console.error('Semester select element not found');
            }

            // Load semesters when add subject section is shown
            const addSubjectLink = document.getElementById('add-subject');
            if (addSubjectLink) {
                addSubjectLink.addEventListener('click', () => {
                    console.log('Add subject clicked');
                    loadActiveSemesters();
                });
            }
        });

        function handleSemesterSelection() {
            const semesterSelect = document.getElementById('semester-select');
            const infoTable = document.getElementById('semester-info-table');
            const semesterId = semesterSelect.value;

            if (!semesterId) {
                infoTable.style.display = 'none';
                return;
            }

            infoTable.style.display = 'block';
            infoTable.innerHTML = '<div class="loading">Loading semester details...</div>';

            fetch(`get_semester_details.php?semester_id=${encodeURIComponent(semesterId)}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data && result.data.length > 0) {
                        let html = `
                            <div class="semester-details-container">
                                <div class="semester-header">
                                    <h3>${result.data[0].semester_name || 'N/A'}</h3>
                                </div>
                                <div class="courses-container">`;

                        result.data.forEach((item, index) => {
                            const startDate = item.start_date ? new Date(item.start_date).toLocaleDateString() : 'Not set';
                            const endDate = item.end_date ? new Date(item.end_date).toLocaleDateString() : 'Not set';
                            
                            html += `
                                <div class="course-card">
                                    <div class="course-header">
                                        <span class="course-number">Course ${index + 1}</span>
                                    </div>
                                    <div class="course-body">
                                        <div class="detail-row">
                                            <span class="label">Course Name:</span>
                                            <span class="value">${item.course_name || 'N/A'}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">Course Code:</span>
                                            <span class="value">${item.course_code || 'N/A'}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">Department:</span>
                                            <span class="value">${item.department_name}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">Dept. Code:</span>
                                            <span class="value">${item.department_code}</span>
                                        </div>
                                        <div class="detail-row duration">
                                            <span class="label">Duration:</span>
                                            <span class="value">${startDate} - ${endDate}</span>
                                        </div>
                                    </div>
                                </div>`;
                        });

                        html += `
                                </div>
                            </div>`;
                        
                        infoTable.innerHTML = html;
                    } else {
                        throw new Error(result.message || 'Failed to load semester details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    infoTable.innerHTML = `
                        <div class="error-message">
                            Error loading semester details: ${error.message}
                        </div>`;
                });
        }

        function showEnabledDepartments() {
            // Hide other tables
            document.getElementById('enabled-courses-table').style.display = 'none';
            document.getElementById('enabled-subjects-table').style.display = 'none';
            
            // Show departments table
            const departmentsTable = document.getElementById('enabled-departments-table');
            departmentsTable.style.display = 'block';
            
            // Fetch enabled departments
            fetch('get_enabled_departments.php')
                .then(response => response.json())
                .then(departments => {
                    const tbody = document.getElementById('enabled-departments-list');
                    tbody.innerHTML = '';
                    if (departments.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="2" style="text-align: center;">No departments found</td></tr>';
                    } else {
                        departments.forEach(dept => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${dept.name || ''}</td>
                                    <td>${dept.code || ''}</td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const tbody = document.getElementById('enabled-departments-list');
                    tbody.innerHTML = '<tr><td colspan="2" style="text-align: center;">Error loading departments</td></tr>';
                });
        }

        function showEnabledCourses() {
            // Hide other tables
            document.getElementById('enabled-departments-table').style.display = 'none';
            document.getElementById('enabled-subjects-table').style.display = 'none';
            
            // Show courses table
            const coursesTable = document.getElementById('enabled-courses-table');
            coursesTable.style.display = 'block';
            
            // Fetch enabled courses
            fetch('get_enabled_courses.php')
                .then(response => response.json())
                .then(courses => {
                    const tbody = document.getElementById('enabled-courses-list');
                    tbody.innerHTML = '';
                    if (courses.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No courses found</td></tr>';
                    } else {
                        courses.forEach(course => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${course.name || ''}</td>
                                    <td>${course.code || ''}</td>
                                    <td>${course.department || ''}</td>
                                    <td>${course.num_semesters || ''}</td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const tbody = document.getElementById('enabled-courses-list');
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Error loading courses</td></tr>';
                });
        }

        function showEnabledSubjects() {
            // Hide other tables
            document.getElementById('enabled-departments-table').style.display = 'none';
            document.getElementById('enabled-courses-table').style.display = 'none';
            
            // Show subjects table
            const subjectsTable = document.getElementById('enabled-subjects-table');
            subjectsTable.style.display = 'block';
            
            // Fetch enabled subjects
            fetch('get_enabled_subjects.php')
                .then(response => response.json())
                .then(subjects => {
                    const tbody = document.getElementById('enabled-subjects-list');
                    tbody.innerHTML = '';
                    if (subjects.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="2" style="text-align: center;">No subjects found</td></tr>';
                    } else {
                        subjects.forEach(subject => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${subject.name || ''}</td>
                                    <td>${subject.has_credits ? (subject.credit_points || 'N/A') : 'N/A'}</td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const tbody = document.getElementById('enabled-subjects-list');
                    tbody.innerHTML = '<tr><td colspan="2" style="text-align: center;">Error loading subjects</td></tr>';
                });
        }

        // Add these functions to your existing JavaScript
        function loadDepartmentsForSemester() {
            fetch('get_enabled_departments.php')
                .then(response => response.json())
                .then(data => {
                    const departmentSelect = document.getElementById('department-select-semester');
                    departmentSelect.innerHTML = '<option value="">Select Department</option>';
                    
                    if (data.success && data.data) {
                        data.data.forEach(dept => {
                            departmentSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading departments:', error));
        }

        function loadCoursesForDepartment(departmentId) {
            const courseSelect = document.getElementById('course-select');
            courseSelect.innerHTML = '<option value="">Loading courses...</option>';
            courseSelect.disabled = true;

            fetch(`get_courses_by_department.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(result => {
                    courseSelect.innerHTML = '<option value="">Select Course</option>';
                    
                    if (result.success && result.data) {
                        result.data.forEach(course => {
                            courseSelect.innerHTML += `<option value="${course.id}">${course.name}</option>`;
                        });
                        courseSelect.disabled = false;
                    } else {
                        courseSelect.innerHTML = '<option value="">No courses available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                });
        }

        // Add event listener for department selection
        document.getElementById('department-select-semester').addEventListener('change', function() {
            const departmentId = this.value;
            const courseSelect = document.getElementById('course-select');
            
            if (departmentId) {
                loadCoursesForDepartment(departmentId);
            } else {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                courseSelect.disabled = true;
            }
        });

        // Add these functions to your existing JavaScript
        function loadDepartmentsForSubject() {
            fetch('get_departments_for_semester.php')
                .then(response => response.json())
                .then(result => {
                    const select = document.getElementById('department-select-subject');
                    select.innerHTML = '<option value="">Select Department</option>';
                    
                    if (result.success && result.data) {
                        result.data.forEach(dept => {
                            select.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function loadCoursesForSubject(departmentId) {
            const courseSelect = document.getElementById('course-select-subject');
            const semesterSelect = document.getElementById('semester-select');
            
            courseSelect.innerHTML = '<option value="">Loading courses...</option>';
            courseSelect.disabled = true;
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            semesterSelect.disabled = true;

            fetch(`get_courses_by_department.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(result => {
                    courseSelect.innerHTML = '<option value="">Select Course</option>';
                    
                    if (result.success && result.data) {
                        result.data.forEach(course => {
                            courseSelect.innerHTML += `<option value="${course.id}">${course.name}</option>`;
                        });
                        courseSelect.disabled = false;
                    } else {
                        courseSelect.innerHTML = '<option value="">No courses available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                });
        }

        function loadSemestersForCourse(courseId) {
            const semesterSelect = document.getElementById('semester-select');
            semesterSelect.innerHTML = '<option value="">Loading semesters...</option>';
            semesterSelect.disabled = true;

            fetch(`get_course_semesters.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(result => {
                    semesterSelect.innerHTML = '<option value="">Select Semester</option>';
                    
                    if (result.success && result.data) {
                        result.data.forEach(semester => {
                            semesterSelect.innerHTML += `<option value="${semester.id}">${semester.name}</option>`;
                        });
                        semesterSelect.disabled = false;
                    } else {
                        semesterSelect.innerHTML = '<option value="">No semesters available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    semesterSelect.innerHTML = '<option value="">Error loading semesters</option>';
                });
        }

        // Update the add-subject click handler
        document.getElementById('add-subject').addEventListener('click', () => {
            showSection(addSubjectSection);
            loadDepartmentsForSubject();
            document.getElementById('course-select-subject').disabled = true;
            document.getElementById('semester-select').disabled = true;
            subjectDropdown.style.display = 'none';
            setActiveLink(subjectLink);
        });

        // Add event listeners for the cascading dropdowns
        document.getElementById('department-select-subject').addEventListener('change', function() {
            const departmentId = this.value;
            if (departmentId) {
                loadCoursesForSubject(departmentId);
            } else {
                document.getElementById('course-select-subject').innerHTML = '<option value="">Select Course</option>';
                document.getElementById('course-select-subject').disabled = true;
                document.getElementById('semester-select').innerHTML = '<option value="">Select Semester</option>';
                document.getElementById('semester-select').disabled = true;
            }
        });

        document.getElementById('course-select-subject').addEventListener('change', function() {
            const courseId = this.value;
            if (courseId) {
                loadSemestersForCourse(courseId);
            } else {
                document.getElementById('semester-select').innerHTML = '<option value="">Select Semester</option>';
                document.getElementById('semester-select').disabled = true;
            }
        });

        // Add this function to load enabled departments
        function loadEnabledDepartmentsForStaff(selectElementId) {
            const select = document.getElementById(selectElementId);
            select.innerHTML = '<option value="">Loading departments...</option>';
            
            console.log("Fetching departments...");
            fetch('get_enabled_departments.php')
                .then(response => {
                    console.log("Response status:", response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text(); // Get raw text first for debugging
                })
                .then(text => {
                    console.log("Raw response:", text);
                    try {
                        return JSON.parse(text); // Now parse it
                    } catch (e) {
                        console.error("JSON parse error:", e);
                        throw new Error("Invalid JSON response");
                    }
                })
                .then(result => {
                    console.log("Parsed result:", result);
                    select.innerHTML = '<option value="">Select Department</option>';
                    
                    if (result.success && Array.isArray(result.data) && result.data.length > 0) {
                        result.data.forEach(dept => {
                            select.innerHTML += `<option value="${dept.id}">${dept.name} (${dept.code})</option>`;
                        });
                    } else if (result.success && Array.isArray(result.data) && result.data.length === 0) {
                        select.innerHTML += '<option value="" disabled>No departments available</option>';
                    } else if (!result.success) {
                        console.error('Server error:', result.message);
                        select.innerHTML = '<option value="">Error: ' + (result.message || 'Server error') + '</option>';
                    } else {
                        console.error('Unexpected data format:', result);
                        select.innerHTML = '<option value="">Error: Unexpected data format</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading departments:', error);
                    select.innerHTML = '<option value="">Error: ' + error.message + '</option>';
                });
        }

        // Update click handlers to load departments
        document.getElementById('add-teacher').addEventListener('click', () => {
            showSection(addTeacherSection);
            loadEnabledDepartmentsForStaff('teacher-department');
            teacherDropdown.style.display = 'none';
            setActiveLink(teacherLink);
        });

        document.getElementById('add-hod').addEventListener('click', () => {
            showSection(addHodSection);
            loadEnabledDepartmentsForStaff('hod-department');
            hodDropdown.style.display = 'none';
            setActiveLink(hodLink);
        });

        // Remove all existing event listeners for the form
        const teacherForm = document.getElementById('add-teacher-form');
        const newTeacherForm = teacherForm.cloneNode(true);
        teacherForm.parentNode.replaceChild(newTeacherForm, teacherForm);

        // Update the teacher form submission:
        newTeacherForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Get form values
            const email = this.querySelector('#teacher-email').value;
            const department = this.querySelector('#teacher-department').value;
            const designation = this.querySelector('#teacher-designation').value;
            
            // Validate required fields
            if (!email || !department || !designation) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Disable form elements
            const allInputs = this.querySelectorAll('input, select, button');
            allInputs.forEach(input => input.disabled = true);
            
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.textContent = 'Adding...';
            
            // Create FormData with all required fields
            const formData = new FormData();
            formData.append('email', email);
            formData.append('role', 'teacher');
            formData.append('department_id', department);
            formData.append('designation', designation); // Add designation
            
            // Send request
            fetch('add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message || 'Teacher added successfully!');
                    this.reset();
                } else {
                    alert(result.message || 'Error adding teacher');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Re-enable form elements
                allInputs.forEach(input => input.disabled = false);
                submitButton.textContent = 'Add Teacher';
            });
        });

        // Similarly update the HOD form handler
        document.getElementById('add-hod-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Get form values
            const email = this.querySelector('#hod-email').value;
            const department = this.querySelector('#hod-department').value;
            
            // Validate required fields
            if (!email || !department) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Disable form elements
            const allInputs = this.querySelectorAll('input, select, button');
            allInputs.forEach(input => input.disabled = true);
            
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.textContent = 'Adding...';
            
            // Create FormData with all required fields
            const formData = new FormData();
            formData.append('email', email);
            formData.append('role', 'hod');
            formData.append('department_id', department); // Make sure this matches the PHP parameter name
            
            // Log what's being sent
            console.log('Sending HOD data to server:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Send request
            fetch('add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('HOD added successfully!');
                    this.reset();
                    const deptSelect = document.getElementById('hod-department');
                    deptSelect.innerHTML = '<option value="">Select Department</option>';
                    loadEnabledDepartmentsForStaff('hod-department');
                } else {
                    alert(result.message || 'Error adding HOD');
                    console.error('Error details:', result);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding HOD. Please check console for details.');
            })
            .finally(() => {
                // Re-enable form elements
                allInputs.forEach(input => {
                    if (input.id !== 'hod-department') {
                        input.disabled = false;
                    }
                });
                submitButton.textContent = 'Add HOD';
            });
        });

        // Update the View Courses Section with search options that can expand
        document.getElementById('view-courses-section').innerHTML = `
            <h2>View Courses</h2>
            
            <div id="search-container" class="search-container" style="display: flex; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
                <div id="dept-search-option" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Department</h3>
                    <div class="form-group">
                        <select id="department-search" class="search-input">
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div id="department-courses-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Number of Semesters</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="name-search-option" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Name</h3>
                    <div class="form-group">
                        <input type="text" id="course-name-search" class="search-input" placeholder="Enter course name">
                        <button id="search-by-name-btn">Search</button>
                    </div>
                    <div id="course-name-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Department</th>
                                    <th>Number of Semesters</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="code-search-option" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Code</h3>
                    <div class="form-group">
                        <input type="text" id="course-code-search" class="search-input" placeholder="Enter course code">
                        <button id="search-by-code-btn">Search</button>
                    </div>
                    <div id="course-code-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Department</th>
                                    <th>Number of Semesters</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="back-to-search" style="display: none; margin-bottom: 20px;">
                <button class="btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                    ← Back to Search Options
                </button>
            </div>
        `;

        // Update this function to load courses from a specific department with full width results
        function loadCoursesByDepartment(departmentId) {
            // Expand the department search option to full width
            document.getElementById('dept-search-option').style.width = '100%';
            document.getElementById('name-search-option').style.display = 'none';
            document.getElementById('code-search-option').style.display = 'none';
            document.getElementById('back-to-search').style.display = 'block';
            
            const resultsContainer = document.getElementById('department-courses-results');
            const resultsTable = resultsContainer.querySelector('table');
            const tableBody = resultsTable.querySelector('tbody');
            
            tableBody.innerHTML = '<tr><td colspan="5">Loading...</td></tr>';
            resultsTable.style.display = 'table';
            resultsTable.style.width = '100%';
            
            fetch(`get_department_courses.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        tableBody.innerHTML = '';
                        result.data.forEach(course => {
                            const statusClass = course.is_disabled ? 'status-disabled' : 'status-enabled';
                            const statusText = course.is_disabled ? 'Disabled' : 'Enabled';
                            const actionBtn = course.is_disabled ? 
                                `<button class="enable-btn" data-id="${course.id}">Enable</button>` : 
                                `<button class="disable-btn" data-id="${course.id}">Disable</button>`;
                            
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${course.name}</td>
                                    <td>${course.code}</td>
                                    <td>${course.num_semesters}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td>${actionBtn}</td>
                                </tr>
                            `;
                        });
                        
                        // Add event listeners to the enable/disable buttons
                        addActionButtonListeners();
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="5">No courses found for this department</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error loading courses:', error);
                    tableBody.innerHTML = '<tr><td colspan="5">Error loading courses</td></tr>';
                });
        }

        // Update this function to search courses by name with full width results
        function searchCoursesByName(courseName) {
            // Expand the name search option to full width
            document.getElementById('name-search-option').style.width = '100%';
            document.getElementById('dept-search-option').style.display = 'none';
            document.getElementById('code-search-option').style.display = 'none';
            document.getElementById('back-to-search').style.display = 'block';
            
            const resultsContainer = document.getElementById('course-name-results');
            const resultsTable = resultsContainer.querySelector('table');
            const tableBody = resultsTable.querySelector('tbody');
            
            tableBody.innerHTML = '<tr><td colspan="6">Searching...</td></tr>';
            resultsTable.style.display = 'table';
            resultsTable.style.width = '100%';
            
            fetch(`search_courses.php?name=${encodeURIComponent(courseName)}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        tableBody.innerHTML = '';
                        result.data.forEach(course => {
                            const statusClass = course.is_disabled ? 'status-disabled' : 'status-enabled';
                            const statusText = course.is_disabled ? 'Disabled' : 'Enabled';
                            const actionBtn = course.is_disabled ? 
                                `<button class="enable-btn" data-id="${course.id}">Enable</button>` : 
                                `<button class="disable-btn" data-id="${course.id}">Disable</button>`;
                            
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${course.name}</td>
                                    <td>${course.code}</td>
                                    <td>${course.department_name}</td>
                                    <td>${course.num_semesters}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td>${actionBtn}</td>
                                </tr>
                            `;
                        });
                        
                        // Add event listeners to the enable/disable buttons
                        addActionButtonListeners();
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="6">No courses found matching that name</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error searching courses:', error);
                    tableBody.innerHTML = '<tr><td colspan="6">Error searching courses</td></tr>';
                });
        }

        // Update this function to search courses by code with full width results
        function searchCoursesByCode(courseCode) {
            // Expand the code search option to full width
            document.getElementById('code-search-option').style.width = '100%';
            document.getElementById('dept-search-option').style.display = 'none';
            document.getElementById('name-search-option').style.display = 'none';
            document.getElementById('back-to-search').style.display = 'block';
            
            const resultsContainer = document.getElementById('course-code-results');
            const resultsTable = resultsContainer.querySelector('table');
            const tableBody = resultsTable.querySelector('tbody');
            
            tableBody.innerHTML = '<tr><td colspan="6">Searching...</td></tr>';
            resultsTable.style.display = 'table';
            resultsTable.style.width = '100%';
            
            fetch(`search_courses.php?code=${encodeURIComponent(courseCode)}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        tableBody.innerHTML = '';
                        result.data.forEach(course => {
                            const statusClass = course.is_disabled ? 'status-disabled' : 'status-enabled';
                            const statusText = course.is_disabled ? 'Disabled' : 'Enabled';
                            const actionBtn = course.is_disabled ? 
                                `<button class="enable-btn" data-id="${course.id}">Enable</button>` : 
                                `<button class="disable-btn" data-id="${course.id}">Disable</button>`;
                            
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${course.name}</td>
                                    <td>${course.code}</td>
                                    <td>${course.department_name}</td>
                                    <td>${course.num_semesters}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td>${actionBtn}</td>
                                </tr>
                            `;
                        });
                        
                        // Add event listeners to the enable/disable buttons
                        addActionButtonListeners();
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="6">No courses found matching that code</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error searching courses:', error);
                    tableBody.innerHTML = '<tr><td colspan="6">Error searching courses</td></tr>';
                });
        }

        // Add event listeners for search functionality including Back button
        document.getElementById('view-courses').addEventListener('click', function() {
            showSection(viewCoursesSection);
            setActiveLink(courseLink);
            courseDropdown.style.display = 'block';
            
            // Reset search options display
            resetSearchDisplay();
            
            // Load departments for search after showing the section
            setTimeout(() => {
                loadDepartmentsForSearch();
                
                // Add event listeners for search functionality
                const departmentSearch = document.getElementById('department-search');
                if (departmentSearch) {
                    departmentSearch.addEventListener('change', function() {
                        if (this.value) {
                            loadCoursesByDepartment(this.value);
                        } else {
                            const resultsTable = document.querySelector('#department-courses-results table');
                            if (resultsTable) {
                                resultsTable.style.display = 'none';
                            }
                        }
                    });
                }
                
                const searchByNameBtn = document.getElementById('search-by-name-btn');
                if (searchByNameBtn) {
                    searchByNameBtn.addEventListener('click', function() {
                        const courseName = document.getElementById('course-name-search').value.trim();
                        if (courseName) {
                            searchCoursesByName(courseName);
                        } else {
                            alert('Please enter a course name to search');
                        }
                    });
                }
                
                const searchByCodeBtn = document.getElementById('search-by-code-btn');
                if (searchByCodeBtn) {
                    searchByCodeBtn.addEventListener('click', function() {
                        const courseCode = document.getElementById('course-code-search').value.trim();
                        if (courseCode) {
                            searchCoursesByCode(courseCode);
                        } else {
                            alert('Please enter a course code to search');
                        }
                    });
                }
                
                // Add back button functionality
                const backBtn = document.querySelector('#back-to-search button');
                if (backBtn) {
                    backBtn.addEventListener('click', resetSearchDisplay);
                }
            }, 100);
        });

        // Function to reset the search display back to side by side
        function resetSearchDisplay() {
            // Reset all search options to default view
            document.getElementById('dept-search-option').style.width = '';
            document.getElementById('dept-search-option').style.display = '';
            document.getElementById('name-search-option').style.width = '';
            document.getElementById('name-search-option').style.display = '';
            document.getElementById('code-search-option').style.width = '';
            document.getElementById('code-search-option').style.display = '';
            document.getElementById('back-to-search').style.display = 'none';
            
            // Hide all result tables
            const resultTables = document.querySelectorAll('.search-results-table');
            resultTables.forEach(table => {
                table.style.display = 'none';
            });
        }

        // Add stylesheet for search components
        const searchStyles = document.createElement('style');
        searchStyles.textContent = `
            .search-container {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .search-option {
                background-color: white;
                padding: 15px;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .search-option h3 {
                margin-top: 0;
                color: #2c3e50;
                font-size: 1.1em;
                margin-bottom: 15px;
            }
            
            .search-input {
                width: 100%;
                padding: 8px;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .search-results-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            
            .search-results-table th,
            .search-results-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            
            .search-results-table th {
                background-color: #2c3e50;
                color: white;
            }
            
            .search-results-table tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            
            .search-results-table tr:hover {
                background-color: #e9ecef;
            }
            
            #search-by-name-btn,
            #search-by-code-btn {
                background-color: #2c3e50;
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 4px;
                cursor: pointer;
            }
            
            #search-by-name-btn:hover,
            #search-by-code-btn:hover {
                background-color: #1a252f;
            }
        `;
        document.head.appendChild(searchStyles);

        // Add this function to load departments into the search dropdown
        function loadDepartmentsForSearch() {
            const departmentSearch = document.getElementById('department-search');
            if (!departmentSearch) return;
            
            fetch('get_departments.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        departmentSearch.innerHTML = '<option value="">Select Department</option>';
                        result.data.forEach(dept => {
                            departmentSearch.innerHTML += `<option value="${dept.id}">${dept.name} (${dept.code})</option>`;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading departments:', error);
                });
        }

        // Add this function to handle enable/disable actions
        function addActionButtonListeners() {
            // For disable buttons
            document.querySelectorAll('.disable-btn').forEach(button => {
                if (!button.hasAttribute('data-initialized')) {
                    button.setAttribute('data-initialized', 'true');
                    button.addEventListener('click', function() {
                        const courseId = this.getAttribute('data-id');
                        const row = this.closest('tr');
                        disableCourse(courseId, row);
                    });
                }
            });
            
            // For enable buttons
            document.querySelectorAll('.enable-btn').forEach(button => {
                if (!button.hasAttribute('data-initialized')) {
                    button.setAttribute('data-initialized', 'true');
                    button.addEventListener('click', function() {
                        const courseId = this.getAttribute('data-id');
                        const row = this.closest('tr');
                        enableCourse(courseId, row);
                    });
                }
            });
        }

        // Function to disable a course with flexible cell selection
        function disableCourse(courseId, row) {
            fetch('toggle_course_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `course_id=${courseId}&status=disable`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Find the status cell by looking for the cell before the action cell
                    const cells = row.querySelectorAll('td');
                    const cellCount = cells.length;
                    const statusCell = cells[cellCount - 2]; // Second-to-last cell is status
                    const actionCell = cells[cellCount - 1]; // Last cell is action
                    
                    // Update status and action cells
                    if (statusCell && actionCell) {
                        statusCell.innerHTML = '<span class="status-disabled">Disabled</span>';
                    actionCell.innerHTML = `<button class="enable-btn" data-id="${courseId}">Enable</button>`;
                    
                    // Add event listener to the new button
                    addActionButtonListeners();
                    
                    // Show success message
                    alert('Course disabled successfully');
                    } else {
                        console.error('Could not find status or action cells');
                        alert('Error updating display: Could not find table cells');
                    }
                } else {
                    alert(result.message || 'Error disabling course');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error disabling course. Please try again: ' + error.message);
            });
        }

        // Function to enable a course with flexible cell selection
        function enableCourse(courseId, row) {
            fetch('toggle_course_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `course_id=${courseId}&status=enable`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Find the status cell by looking for the cell before the action cell
                    const cells = row.querySelectorAll('td');
                    const cellCount = cells.length;
                    const statusCell = cells[cellCount - 2]; // Second-to-last cell is status
                    const actionCell = cells[cellCount - 1]; // Last cell is action
                    
                    // Update status and action cells
                    if (statusCell && actionCell) {
                        statusCell.innerHTML = '<span class="status-enabled">Enabled</span>';
                    actionCell.innerHTML = `<button class="disable-btn" data-id="${courseId}">Disable</button>`;
                    
                    // Add event listener to the new button
                    addActionButtonListeners();
                    
                    // Show success message
                    alert('Course enabled successfully');
                    } else {
                        console.error('Could not find status or action cells');
                        alert('Error updating display: Could not find table cells');
                    }
                } else {
                    alert(result.message || 'Error enabling course');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error enabling course. Please try again: ' + error.message);
            });
        }

        // Update the View Semesters Section with search options
        document.getElementById('view-semesters-section').innerHTML = `
            <h2>View Semesters</h2>
            
            <div id="semester-search-container" class="search-container" style="display: flex; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
                <div id="dept-semester-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Department</h3>
                    <div class="form-group">
                        <select id="department-semester-search" class="search-input">
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div id="department-semesters-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Semester</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="course-name-semester-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Name</h3>
                    <div class="form-group">
                        <input type="text" id="course-name-semester-search-input" class="search-input" placeholder="Enter course name">
                        <button id="search-semester-by-course-name-btn">Search</button>
                    </div>
                    <div id="course-name-semesters-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Course Code</th>
                                    <th>Semester</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="course-code-semester-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Course Code</h3>
                    <div class="form-group">
                        <input type="text" id="course-code-semester-search-input" class="search-input" placeholder="Enter course code">
                        <button id="search-semester-by-course-code-btn">Search</button>
                    </div>
                    <div id="course-code-semesters-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Course Name</th>
                                    <th>Semester</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="semester-name-search" class="search-option" style="flex: 1; min-width: 300px;">
                    <h3>Search by Semester Name</h3>
                    <div class="form-group">
                        <input type="text" id="semester-name-search-input" class="search-input" placeholder="Enter semester name">
                        <button id="search-by-semester-name-btn">Search</button>
                    </div>
                    <div id="semester-name-results" class="search-results">
                        <table class="search-results-table" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Course Name</th>
                                    <th>Course Code</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="back-to-semester-search" style="display: none; margin-bottom: 20px;">
                <button class="btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">
                    ← Back to Search Options
                </button>
            </div>
        `;

        // Function to load departments for semester search
        function loadDepartmentsForSemesterSearch() {
            const departmentSearch = document.getElementById('department-semester-search');
            if (!departmentSearch) {
                console.error('Department search element not found');
                return;
            }
            
            console.log('Loading departments for semester search...');
            
            fetch('get_departments.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    console.log('Departments loaded:', result);
                    if (result.success) {
                        departmentSearch.innerHTML = '<option value="">Select Department</option>';
                        result.data.forEach(dept => {
                                departmentSearch.innerHTML += `<option value="${dept.id}">${dept.name} (${dept.code})</option>`;
                        });
                    } else {
                        console.error('Failed to load departments:', result.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading departments for semester search:', error);
                });
        }

        // Function to search semesters by department
        function searchSemestersByDepartment(departmentId) {
            console.log('Searching semesters by department ID:', departmentId);
            
            try {
                // Expand the department search option to full width
                const deptSearchElement = document.getElementById('dept-semester-search');
                if (!deptSearchElement) {
                    console.error('Department search element not found');
                    alert('Error: Department search section not found');
                    return;
                }
                deptSearchElement.style.width = '100%';
                
                // Hide other search options
                ['course-name-semester-search', 'course-code-semester-search', 'semester-name-search'].forEach(id => {
                    const element = document.getElementById(id);
                    if (element) element.style.display = 'none';
                });
                
                // Show back button
                const backBtn = document.getElementById('back-to-semester-search');
                if (backBtn) backBtn.style.display = 'block';
                
                // Get results container and table
                const resultsContainer = document.getElementById('department-semesters-results');
                if (!resultsContainer) {
                    console.error('Results container not found');
                    alert('Error: Results container not found');
                    return;
                }
                
                const resultsTable = resultsContainer.querySelector('table');
                if (!resultsTable) {
                    console.error('Results table not found');
                    alert('Error: Results table not found');
                    return;
                }
                
                const tableBody = resultsTable.querySelector('tbody');
                if (!tableBody) {
                    console.error('Table body not found');
                    alert('Error: Table body not found');
                    return;
                }
                
                // Show loading message
                tableBody.innerHTML = '<tr><td colspan="7">Loading...</td></tr>';
                resultsTable.style.display = 'table';
                resultsTable.style.width = '100%';
                
                // Make the API request
                fetch(`get_department_semesters.php?department_id=${departmentId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(result => {
                        console.log('Department semesters response:', result);
                        
                        if (result.success && result.data && result.data.length > 0) {
                            tableBody.innerHTML = '';
                            
                            result.data.forEach(semester => {
                                const statusClass = semester.is_disabled ? 'status-disabled' : 'status-enabled';
                                const statusText = semester.is_disabled ? 'Disabled' : 'Enabled';
                                const actionBtn = semester.is_disabled ? 
                                    `<button class="enable-btn" data-id="${semester.id}">Enable</button>` : 
                                    `<button class="disable-btn" data-id="${semester.id}">Disable</button>`;
                                
                                tableBody.innerHTML += `
                                    <tr>
                                        <td>${semester.course_name || 'N/A'}</td>
                                        <td>${semester.course_code || 'N/A'}</td>
                                        <td>${semester.name || 'N/A'}</td>
                                        <td>${semester.start_date || 'N/A'}</td>
                                        <td>${semester.end_date || 'N/A'}</td>
                                        <td><span class="${statusClass}">${statusText}</span></td>
                                        <td>${actionBtn}</td>
                                    </tr>
                                `;
                            });
                            
                            // Add event listeners to the enable/disable buttons
                            addSemesterActionButtonListeners();
                        } else {
                            tableBody.innerHTML = '<tr><td colspan="7">No semesters found for this department</td></tr>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading semesters:', error);
                        tableBody.innerHTML = `<tr><td colspan="7">Error loading semesters: ${error.message}</td></tr>`;
                    });
            } catch (error) {
                console.error('Error in searchSemestersByDepartment:', error);
                alert('An error occurred while searching semesters: ' + error.message);
            }
        }

        // Function to search semesters by course name
        function searchSemestersByCourseName(courseName) {
            console.log('Searching semesters by course name:', courseName);
            
            // Expand this search option to full width
            document.getElementById('course-name-semester-search').style.width = '100%';
            document.getElementById('dept-semester-search').style.display = 'none';
            document.getElementById('course-code-semester-search').style.display = 'none';
            document.getElementById('semester-name-search').style.display = 'none';
            document.getElementById('back-to-semester-search').style.display = 'block';
            
            const resultsContainer = document.getElementById('course-name-semesters-results');
            const resultsTable = resultsContainer.querySelector('table');
            const tableBody = resultsTable.querySelector('tbody');
            
            tableBody.innerHTML = '<tr><td colspan="7">Searching...</td></tr>';
            resultsTable.style.display = 'table';
            resultsTable.style.width = '100%';
            
            fetch(`search_semesters.php?course_name=${encodeURIComponent(courseName)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    console.log('Course name search results:', result);
                    if (result.success && result.data.length > 0) {
                        tableBody.innerHTML = '';
                        result.data.forEach(semester => {
                            const statusClass = semester.is_disabled ? 'status-disabled' : 'status-enabled';
                            const statusText = semester.is_disabled ? 'Disabled' : 'Enabled';
                            const actionBtn = semester.is_disabled ? 
                                `<button class="enable-btn" data-id="${semester.id}">Enable</button>` : 
                                `<button class="disable-btn" data-id="${semester.id}">Disable</button>`;
                            
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${semester.department_name}</td>
                                    <td>${semester.course_code}</td>
                                    <td>${semester.name}</td>
                                    <td>${semester.start_date}</td>
                                    <td>${semester.end_date}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td>${actionBtn}</td>
                                </tr>
                            `;
                        });
                        
                        // Add event listeners to the enable/disable buttons
                        addSemesterActionButtonListeners();
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="7">No semesters found matching that course name</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error searching semesters by course name:', error);
                    tableBody.innerHTML = `<tr><td colspan="7">Error searching semesters: ${error.message}</td></tr>`;
                });
        }

        // Function to search semesters by course code
        function searchSemestersByCourseCode(courseCode) {
            // Expand the course code search option to full width
            document.getElementById('course-code-semester-search').style.width = '100%';
            document.getElementById('dept-semester-search').style.display = 'none';
            document.getElementById('course-name-semester-search').style.display = 'none';
            document.getElementById('semester-name-search').style.display = 'none';
            document.getElementById('back-to-semester-search').style.display = 'block';
            
            const resultsContainer = document.getElementById('course-code-semesters-results');
            const resultsTable = resultsContainer.querySelector('table');
            const tableBody = resultsTable.querySelector('tbody');
            
            tableBody.innerHTML = '<tr><td colspan="7">Searching...</td></tr>';
            resultsTable.style.display = 'table';
            resultsTable.style.width = '100%';
            
            fetch(`search_semesters.php?course_code=${encodeURIComponent(courseCode)}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        tableBody.innerHTML = '';
                        result.data.forEach(semester => {
                            const statusClass = semester.is_disabled ? 'status-disabled' : 'status-enabled';
                            const statusText = semester.is_disabled ? 'Disabled' : 'Enabled';
                            const actionBtn = semester.is_disabled ? 
                                `<button class="enable-btn" data-id="${semester.id}">Enable</button>` : 
                                `<button class="disable-btn" data-id="${semester.id}">Disable</button>`;
                            
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${semester.department_name}</td>
                                    <td>${semester.course_name}</td>
                                    <td>${semester.name}</td>
                                    <td>${semester.start_date}</td>
                                    <td>${semester.end_date}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td>${actionBtn}</td>
                                </tr>
                            `;
                        });
                        
                        // Add event listeners to the enable/disable buttons
                        addSemesterActionButtonListeners();
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="7">No semesters found matching that course code</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error searching semesters:', error);
                    tableBody.innerHTML = '<tr><td colspan="7">Error searching semesters</td></tr>';
                });
        }

        // Function to search by semester name
        function searchBySemesterName(semesterName) {
            // Expand the semester name search option to full width
            document.getElementById('semester-name-search').style.width = '100%';
            document.getElementById('dept-semester-search').style.display = 'none';
            document.getElementById('course-name-semester-search').style.display = 'none';
            document.getElementById('course-code-semester-search').style.display = 'none';
            document.getElementById('back-to-semester-search').style.display = 'block';
            
            const resultsContainer = document.getElementById('semester-name-results');
            const resultsTable = resultsContainer.querySelector('table');
            const tableBody = resultsTable.querySelector('tbody');
            
            tableBody.innerHTML = '<tr><td colspan="7">Searching...</td></tr>';
            resultsTable.style.display = 'table';
            resultsTable.style.width = '100%';
            
            fetch(`search_semesters.php?semester_name=${encodeURIComponent(semesterName)}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        tableBody.innerHTML = '';
                        result.data.forEach(semester => {
                            const statusClass = semester.is_disabled ? 'status-disabled' : 'status-enabled';
                            const statusText = semester.is_disabled ? 'Disabled' : 'Enabled';
                            const actionBtn = semester.is_disabled ? 
                                `<button class="enable-btn" data-id="${semester.id}">Enable</button>` : 
                                `<button class="disable-btn" data-id="${semester.id}">Disable</button>`;
                            
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${semester.department_name}</td>
                                    <td>${semester.course_name}</td>
                                    <td>${semester.course_code}</td>
                                    <td>${semester.start_date}</td>
                                    <td>${semester.end_date}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td>${actionBtn}</td>
                                </tr>
                            `;
                        });
                        
                        // Add event listeners to the enable/disable buttons
                        addSemesterActionButtonListeners();
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="7">No semesters found matching that name</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error searching semesters:', error);
                    tableBody.innerHTML = '<tr><td colspan="7">Error searching semesters</td></tr>';
                });
        }

        // Function to reset the semester search display
        function resetSemesterSearchDisplay() {
            // Reset all search options to default view
            const deptSearch = document.getElementById('dept-semester-search');
            const courseNameSearch = document.getElementById('course-name-semester-search');
            const courseCodeSearch = document.getElementById('course-code-semester-search');
            const semesterNameSearch = document.getElementById('semester-name-search');
            const backToSearch = document.getElementById('back-to-semester-search');
            
            if (deptSearch) deptSearch.style.width = '';
            if (deptSearch) deptSearch.style.display = '';
            if (courseNameSearch) courseNameSearch.style.width = '';
            if (courseNameSearch) courseNameSearch.style.display = '';
            if (courseCodeSearch) courseCodeSearch.style.width = '';
            if (courseCodeSearch) courseCodeSearch.style.display = '';
            if (semesterNameSearch) semesterNameSearch.style.width = '';
            if (semesterNameSearch) semesterNameSearch.style.display = '';
            if (backToSearch) backToSearch.style.display = 'none';
            
            // Hide all result tables
            const resultTables = document.querySelectorAll('.search-results-table');
            resultTables.forEach(table => {
                table.style.display = 'none';
            });
        }

        // Function to add event listeners to semester action buttons
        function addSemesterActionButtonListeners() {
            // For disable buttons
            document.querySelectorAll('.disable-btn').forEach(button => {
                if (!button.hasAttribute('data-initialized')) {
                    button.setAttribute('data-initialized', 'true');
                    button.addEventListener('click', function() {
                        const semesterId = this.getAttribute('data-id');
                        const row = this.closest('tr');
                        disableSemester(semesterId, row);
                    });
                }
            });
            
            // For enable buttons
            document.querySelectorAll('.enable-btn').forEach(button => {
                if (!button.hasAttribute('data-initialized')) {
                    button.setAttribute('data-initialized', 'true');
                    button.addEventListener('click', function() {
                        const semesterId = this.getAttribute('data-id');
                        const row = this.closest('tr');
                        enableSemester(semesterId, row);
                    });
                }
            });
        }

        // Function to disable a semester
        function disableSemester(semesterId, row) {
            fetch('toggle_semester_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `semester_id=${semesterId}&status=disable`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Find the status cell by looking for the cell before the action cell
                    const cells = row.querySelectorAll('td');
                    const cellCount = cells.length;
                    const statusCell = cells[cellCount - 2]; // Second-to-last cell is status
                    const actionCell = cells[cellCount - 1]; // Last cell is action
                    
                    // Update status and action cells
                    if (statusCell && actionCell) {
                        statusCell.innerHTML = '<span class="status-disabled">Disabled</span>';
                        actionCell.innerHTML = `<button class="enable-btn" data-id="${semesterId}">Enable</button>`;
                        
                        // Add event listener to the new button
                        addSemesterActionButtonListeners();
                        
                        // Show success message
                        alert('Semester disabled successfully');
                    } else {
                        console.error('Could not find status or action cells');
                        alert('Error updating display: Could not find table cells');
                    }
                } else {
                    alert(result.message || 'Error disabling semester');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error disabling semester. Please try again: ' + error.message);
            });
        }

        // Function to enable a semester
        function enableSemester(semesterId, row) {
            fetch('toggle_semester_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `semester_id=${semesterId}&status=enable`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Find the status cell by looking for the cell before the action cell
                    const cells = row.querySelectorAll('td');
                    const cellCount = cells.length;
                    const statusCell = cells[cellCount - 2]; // Second-to-last cell is status
                    const actionCell = cells[cellCount - 1]; // Last cell is action
                    
                    // Update status and action cells
                    if (statusCell && actionCell) {
                        statusCell.innerHTML = '<span class="status-enabled">Enabled</span>';
                        actionCell.innerHTML = `<button class="disable-btn" data-id="${semesterId}">Disable</button>`;
                        
                        // Add event listener to the new button
                        addSemesterActionButtonListeners();
                        
                        // Show success message
                        alert('Semester enabled successfully');
                    } else {
                        console.error('Could not find status or action cells');
                        alert('Error updating display: Could not find table cells');
                    }
                } else {
                    alert(result.message || 'Error enabling semester');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error enabling semester. Please try again: ' + error.message);
            });
        }

        // Add event listener to the view semesters link
        document.getElementById('view-semesters').addEventListener('click', function() {
            showSection(viewSemestersSection);
            setActiveLink(semesterLink);
            const semesterDropdown = document.getElementById('semester-dropdown');
            if (semesterDropdown) {
                const isVisible = semesterDropdown.style.display === 'block';
                semesterDropdown.style.display = isVisible ? 'none' : 'block';
            }
            
            // Add debug call here
            debugSemesterSearchElements();
            
            // Reset search options display
            resetSemesterSearchDisplay();
            
            // Load departments for search after showing the section
            setTimeout(() => {
                loadDepartmentsForSemesterSearch();
                
                // Add event listeners for search functionality
                const departmentSearch = document.getElementById('department-semester-search');
                if (departmentSearch) {
                    departmentSearch.addEventListener('change', function() {
                        if (this.value) {
                            searchSemestersByDepartment(this.value);
                        } else {
                            const resultsTable = document.querySelector('#department-semesters-results table');
                            if (resultsTable) {
                                resultsTable.style.display = 'none';
                            }
                        }
                    });
                }
                
                const searchByCourseNameBtn = document.getElementById('search-semester-by-course-name-btn');
                if (searchByCourseNameBtn) {
                    searchByCourseNameBtn.addEventListener('click', function() {
                        const courseName = document.getElementById('course-name-semester-search-input').value.trim();
                        if (courseName) {
                            searchSemestersByCourseName(courseName);
                        } else {
                            alert('Please enter a course name to search');
                        }
                    });
                }
                
                const searchByCourseCodeBtn = document.getElementById('search-semester-by-course-code-btn');
                if (searchByCourseCodeBtn) {
                    searchByCourseCodeBtn.addEventListener('click', function() {
                        const courseCode = document.getElementById('course-code-semester-search-input').value.trim();
                        if (courseCode) {
                            searchSemestersByCourseCode(courseCode);
                        } else {
                            alert('Please enter a course code to search');
                        }
                    });
                }
                
                const searchBySemesterNameBtn = document.getElementById('search-by-semester-name-btn');
                if (searchBySemesterNameBtn) {
                    searchBySemesterNameBtn.addEventListener('click', function() {
                        const semesterName = document.getElementById('semester-name-search-input').value.trim();
                        if (semesterName) {
                            searchBySemesterName(semesterName);
                        } else {
                            alert('Please enter a semester name to search');
                        }
                    });
                }
                
                // Add back button functionality
                const backBtn = document.querySelector('#back-to-semester-search button');
                if (backBtn) {
                    backBtn.addEventListener('click', resetSemesterSearchDisplay);
                }
            }, 100);
        });

        // Add this small debug function at the end of your script
        function debugSemesterSearchElements() {
            console.log('Semester search elements check:', {
                viewSemestersSection: !!document.getElementById('view-semesters-section'),
                departmentSearch: !!document.getElementById('department-semester-search'),
                courseNameSearch: !!document.getElementById('course-name-semester-search-input'),
                courseCodeSearch: !!document.getElementById('course-code-semester-search-input'),
                semesterNameSearch: !!document.getElementById('semester-name-search-input')
            });
        }

        // Add semester search functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Override the view-semesters event listener
            const viewSemestersBtn = document.getElementById('view-semesters');
            if (viewSemestersBtn) {
                // Remove any existing listeners to avoid duplicates
                const newBtn = viewSemestersBtn.cloneNode(true);
                viewSemestersBtn.parentNode.replaceChild(newBtn, viewSemestersBtn);
                
                // Add new event listener
                newBtn.addEventListener('click', function() {
                    // Show the view-semesters-section
                    const viewSemestersSection = document.getElementById('view-semesters-section');
                    
                    // Show this section and hide others
                    showSection(viewSemestersSection);
                    
                    // Set the active link in the navigation
                    const semesterLink = document.getElementById('semester-link') || this;
                    setActiveLink(semesterLink);
                    
                    // Create the semester search interface
                    createSemesterSearchInterface();
                });
            }
            
            // Function to create the semester search interface
            function createSemesterSearchInterface() {
                const section = document.getElementById('view-semesters-section');
                if (!section) return;
                
                section.innerHTML = `
                    <h2>View Semesters</h2>
                    
                    <div class="search-container" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
                        <!-- Search by Course Name -->
                        <div class="search-option" style="flex: 1; min-width: 300px; max-width: 400px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                            <h3>Search by Course Name</h3>
                            <div class="form-group" style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <input type="text" id="course-name-input" placeholder="Enter course name" style="flex: 1; padding: 8px;">
                                <button id="search-course-name-btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
                            </div>
                        </div>
                        
                        <!-- Search by Course Code -->
                        <div class="search-option" style="flex: 1; min-width: 300px; max-width: 400px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                            <h3>Search by Course Code</h3>
                            <div class="form-group" style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <input type="text" id="course-code-input" placeholder="Enter course code" style="flex: 1; padding: 8px;">
                                <button id="search-course-code-btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
                            </div>
                        </div>
                        
                        <!-- Search by Semester Name -->
                        <div class="search-option" style="flex: 1; min-width: 300px; max-width: 400px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                            <h3>Search by Semester Name</h3>
                            <div class="form-group" style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <input type="text" id="semester-name-input" placeholder="Enter semester name" style="flex: 1; padding: 8px;">
                                <button id="search-semester-name-btn" style="background-color: #2c3e50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- This is the full width results container that appears after search -->
                    <div id="semester-results" style="width: 100%; overflow-x: auto;">
                        <table id="semester-table" style="display: none; width: 100%; border-collapse: collapse; margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Department</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Course Code</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Semester</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Start Date</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">End Date</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Status</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                `;
                
                // Add event listeners for Course Name search
                document.getElementById('search-course-name-btn').addEventListener('click', function() {
                    searchSemesters('name');
                });
                document.getElementById('course-name-input').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchSemesters('name');
                    }
                });
                
                // Add event listeners for Course Code search
                document.getElementById('search-course-code-btn').addEventListener('click', function() {
                    searchSemesters('code');
                });
                document.getElementById('course-code-input').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchSemesters('code');
                    }
                });
                
                // Add event listeners for Semester Name search
                document.getElementById('search-semester-name-btn').addEventListener('click', function() {
                    searchSemesters('semester');
                });
                document.getElementById('semester-name-input').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchSemesters('semester');
                    }
                });
            }
            
            // Function to search semesters by name, code, or semester name
            function searchSemesters(searchType) {
                let searchValue, searchParam;
                
                if (searchType === 'name') {
                    searchValue = document.getElementById('course-name-input').value.trim();
                    searchParam = 'course_name';
                    if (!searchValue) {
                        alert('Please enter a course name to search');
                        return;
                    }
                } else if (searchType === 'code') {
                    searchValue = document.getElementById('course-code-input').value.trim();
                    searchParam = 'course_code';
                    if (!searchValue) {
                        alert('Please enter a course code to search');
                        return;
                    }
                } else if (searchType === 'semester') {
                    searchValue = document.getElementById('semester-name-input').value.trim();
                    searchParam = 'semester_name';
                    if (!searchValue) {
                        alert('Please enter a semester name to search');
                        return;
                    }
                }
                
                const table = document.getElementById('semester-table');
                const tableBody = table.querySelector('tbody');
                
                // Show table and loading message
                table.style.display = 'table';
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 10px;">Searching...</td></tr>';
                
                // Make the API call
                fetch(`search_semesters.php?${searchParam}=${encodeURIComponent(searchValue)}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Search results:', data);
                        
                        if (data.success && data.data && data.data.length > 0) {
                            // Display results
                            tableBody.innerHTML = '';
                            data.data.forEach(semester => {
                                const statusClass = semester.is_disabled == 1 ? 'status-disabled' : 'status-enabled';
                                const statusText = semester.is_disabled == 1 ? 'Disabled' : 'Enabled';
                                const actionBtn = semester.is_disabled == 1 ? 
                                    `<button class="enable-btn" data-id="${semester.id}">Enable</button>` : 
                                    `<button class="disable-btn" data-id="${semester.id}">Disable</button>`;
                                
                                tableBody.innerHTML += `
                                    <tr>
                                        <td style="border: 1px solid #ddd; padding: 8px;">${semester.department_name}</td>
                                        <td style="border: 1px solid #ddd; padding: 8px;">${semester.course_code}</td>
                                        <td style="border: 1px solid #ddd; padding: 8px;">${semester.name}</td>
                                        <td style="border: 1px solid #ddd; padding: 8px;">${semester.start_date}</td>
                                        <td style="border: 1px solid #ddd; padding: 8px;">${semester.end_date}</td>
                                        <td style="border: 1px solid #ddd; padding: 8px;"><span class="${statusClass}">${statusText}</span></td>
                                        <td style="border: 1px solid #ddd; padding: 8px;">${actionBtn}</td>
                                    </tr>
                                `;
                            });
                            
                            // Add event listeners to enable/disable buttons
                            addToggleButtonListeners();
                        } else {
                            let searchTypeName = searchType === 'semester' ? 'semester' : `course ${searchType}`;
                            tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 10px;">No semesters found matching that ${searchTypeName}</td></tr>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 10px;">Error searching semesters. Please try again.</td></tr>';
                    });
            }
            
            // Function to add event listeners to toggle buttons
            function addToggleButtonListeners() {
                document.querySelectorAll('.enable-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const semesterId = this.getAttribute('data-id');
                        const row = this.closest('tr');
                        toggleSemesterStatus(semesterId, 'enable', row);
                    });
                });
                
                document.querySelectorAll('.disable-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const semesterId = this.getAttribute('data-id');
                        const row = this.closest('tr');
                        toggleSemesterStatus(semesterId, 'disable', row);
                    });
                });
            }
            
            // Function to toggle semester status
            function toggleSemesterStatus(semesterId, action, row) {
                fetch('toggle_semester_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `semester_id=${semesterId}&status=${action}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Update row
                        const cells = row.querySelectorAll('td');
                        const statusCell = cells[5];
                        const actionCell = cells[6];
                        
                        if (action === 'disable') {
                            statusCell.innerHTML = '<span class="status-disabled">Disabled</span>';
                            actionCell.innerHTML = `<button class="enable-btn" data-id="${semesterId}">Enable</button>`;
                        } else {
                            statusCell.innerHTML = '<span class="status-enabled">Enabled</span>';
                            actionCell.innerHTML = `<button class="disable-btn" data-id="${semesterId}">Disable</button>`;
                        }
                        
                        // Re-add event listeners
                        addToggleButtonListeners();
                        alert(`Semester ${action}d successfully`);
                    } else {
                        alert(result.message || `Failed to ${action} semester`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`Error ${action}ing semester. Please try again.`);
                });
            }
            
            // Add CSS styles
            const styles = document.createElement('style');
            styles.textContent = `
                .status-enabled {
                    color: green;
                    font-weight: bold;
                }
                
                .status-disabled {
                    color: red;
                    font-weight: bold;
                }
                
                .enable-btn, .disable-btn {
                    padding: 5px 10px;
                    border: none;
                    border-radius: 3px;
                    cursor: pointer;
                    font-size: 12px;
                }
                
                .enable-btn {
                    background-color: #28a745;
                    color: white;
                }
                
                .disable-btn {
                    background-color: #dc3545;
                    color: white;
                }
            `;
            document.head.appendChild(styles);
        });

        // Course code search button
        const searchSubjectByCodeBtn = document.getElementById('search-subject-by-code-btn');
        if (searchSubjectByCodeBtn) {
            searchSubjectByCodeBtn.addEventListener('click', searchSubjectsByCourseCode);
        }

        // Course code search on Enter key
        const subjectCourseCodeSearch = document.getElementById('subject-course-code-search');
        if (subjectCourseCodeSearch) {
            subjectCourseCodeSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchSubjectsByCourseCode();
                }
            });
        }

        // Make sure this function is added BEFORE any event listeners that call it
        function searchSubjectsByCourseCode() {
            console.log('Searching subjects by course code - function called');
            
            const courseCode = document.getElementById('subject-course-code-search').value.trim();
            if (!courseCode) {
                alert('Please enter a course code to search');
                return;
            }
            
            console.log('Searching for course code:', courseCode);
            
            const subjectsList = document.getElementById('subjects-list');
            if (!subjectsList) {
                console.error('subjects-list element not found');
                return;
            }
            
            subjectsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Searching subjects...</td></tr>';
            
            // Show reset button
            const resetContainer = document.getElementById('reset-subject-container');
            if (resetContainer) resetContainer.style.display = 'block';
            
            const url = `search_subjects_by_code.php?course_code=${encodeURIComponent(courseCode)}`;
            console.log('Fetch URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Search results:', data);
                    displaySubjects(data);
                })
                .catch(error => {
                    console.error('Error searching subjects by code:', error);
                    subjectsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Error searching subjects</td></tr>';
                });
        }

        // Subject form submission handler
        document.getElementById('add-subject-form').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission
            
            const formData = new FormData(this);
            
            // Submit form data using fetch API
            fetch('add_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Subject added successfully!');
                    // Reset the form
                    this.reset();
                    // Optionally refresh subject list if you have one
                    // loadSubjects();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the subject');
            });
        });

        // Add event listener for subject type change
        document.getElementById('subject-type').addEventListener('change', function() {
            const creditPointsGroup = document.getElementById('credit-points-group');
            const hasCreditsGroup = document.getElementById('has-credits-group');
            
            // Show/hide credit points based on subject type
            if (this.value === 'elective') {
                hasCreditsGroup.style.display = 'block';
                if (document.getElementById('has-credits').checked) {
                    creditPointsGroup.style.display = 'block';
                }
            } else {
                // For theory and lab subjects
                hasCreditsGroup.style.display = 'block';
                if (document.getElementById('has-credits').checked) {
                    creditPointsGroup.style.display = 'block';
                }
            }
        });

        // Replace jQuery code with vanilla JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Search by semester name
            document.getElementById('search-subject-by-semester-btn').addEventListener('click', function() {
                const semesterName = document.getElementById('subject-semester-search').value.trim();
                if (semesterName) {
                    console.log("Searching for semester:", semesterName); // Debug log
                    
                    fetch(`search_subjects.php?search_type=semester&semester_name=${encodeURIComponent(semesterName)}`)
                        .then(response => response.json())
                        .then(response => {
                            console.log("Semester search response:", response); // Debug log
                            if (response.success) {
                                const subjectsList = document.getElementById('subjects-list');
                                subjectsList.innerHTML = '';
                                
                                if (response.subjects.length > 0) {
                                    response.subjects.forEach(function(subject) {
                                        appendSubjectToTable(subject);
                                    });
                                } else {
                                    subjectsList.innerHTML = '<tr><td colspan="6">No subjects found</td></tr>';
                                }
                                document.getElementById('reset-subject-container').style.display = 'block';
                            } else {
                                alert('Error: ' + response.message);
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            alert('Failed to search subjects');
                        });
                }
            });
            
            // Search by subject name
            document.getElementById('search-subject-by-name-btn').addEventListener('click', function() {
                const subjectName = document.getElementById('subject-name-search').value.trim();
                if (subjectName) {
                    console.log("Searching for subject:", subjectName); // Debug log
                    
                    fetch(`search_subjects.php?search_type=subject&subject_name=${encodeURIComponent(subjectName)}`)
                        .then(response => response.json())
                        .then(response => {
                            console.log("Subject search response:", response); // Debug log
                            if (response.success) {
                                const subjectsList = document.getElementById('subjects-list');
                                subjectsList.innerHTML = '';
                                
                                if (response.subjects.length > 0) {
                                    response.subjects.forEach(function(subject) {
                                        appendSubjectToTable(subject);
                                    });
                                } else {
                                    subjectsList.innerHTML = '<tr><td colspan="6">No subjects found</td></tr>';
                                }
                                document.getElementById('reset-subject-container').style.display = 'block';
                            } else {
                                alert('Error: ' + response.message);
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            alert('Failed to search subjects');
                        });
                }
            });
            
            // Helper function to append a subject to the table
            function appendSubjectToTable(subject) {
                // Use is_disabled (0 = enabled, 1 = disabled)
                let status = subject.is_disabled == 0 ? 'Enabled' : 'Disabled';
                let statusClass = subject.is_disabled == 0 ? 'enabled' : 'disabled';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${subject.name}</td>
                    <td>${subject.course_name}</td>
                    <td>${subject.semester_name}</td>
                    <td>${subject.credit_points || '0'}</td>
                    <td class="${statusClass}">${status}</td>
                    <td>
                        <button class="action-btn ${subject.is_disabled == 0 ? 'disable-btn' : 'enable-btn'}" 
                                data-id="${subject.id}" 
                                data-action="${subject.is_disabled == 0 ? 'disable' : 'enable'}">
                            ${subject.is_disabled == 0 ? 'Disable' : 'Enable'}
                        </button>
                    </td>
                `;
                
                document.getElementById('subjects-list').appendChild(row);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Get form elements
            const courseTypeSelect = document.getElementById('course-type');
            const numSemestersInput = document.getElementById('num-semesters');
            const courseNameInput = document.getElementById('course-name');
            const courseCodeInput = document.getElementById('course-code');
            const addCourseForm = document.querySelector('form'); // Adjust if your form has a specific ID
            
            // Add event listener to course type select
            courseTypeSelect.addEventListener('change', function() {
                updateSemesterOptions();
            });
            
            // Function to update semester options based on course type
            function updateSemesterOptions() {
                const courseType = courseTypeSelect.value;
                let allowedValues = [];
                let helpText = '';
                
                // Determine allowed semester values based on course type
                switch(courseType) {
                    case 'UG':
                        allowedValues = [6, 8];
                        helpText = 'UG courses must have 6 or 8 semesters';
                        break;
                    case 'PG':
                        allowedValues = [4];
                        helpText = 'PG courses must have 4 semesters';
                        break;
                    case 'UG+PG':
                        allowedValues = [10, 12];
                        helpText = 'UG+PG courses must have 10 or 12 semesters';
                        break;
                    default:
                        helpText = 'Please select a course type';
                }
                
                // Update the input field
                if (allowedValues.length > 0) {
                    // Set default value
                    numSemestersInput.value = allowedValues[0];
                    
                    // Create dropdown if multiple options
                    if (allowedValues.length > 1) {
                        // Replace the input with a select element
                        const selectElement = document.createElement('select');
                        selectElement.id = 'num-semesters';
                        selectElement.name = 'num-semesters';
                        selectElement.className = numSemestersInput.className;
                        
                        allowedValues.forEach(value => {
                            const option = document.createElement('option');
                            option.value = value;
                            option.textContent = value;
                            selectElement.appendChild(option);
                        });
                        
                        numSemestersInput.parentNode.replaceChild(selectElement, numSemestersInput);
                        numSemestersInput = selectElement;
                    }
                }
                
                // Add help text
                let helpElement = document.getElementById('semester-help');
                if (!helpElement) {
                    helpElement = document.createElement('div');
                    helpElement.id = 'semester-help';
                    helpElement.className = 'help-text';
                    numSemestersInput.parentNode.appendChild(helpElement);
                }
                helpElement.textContent = helpText;
            }
            
            // Form validation
            addCourseForm.addEventListener('submit', function(event) {
                const courseType = courseTypeSelect.value;
                const numSemesters = parseInt(numSemestersInput.value);
                const courseName = courseNameInput.value.trim();
                const courseCode = courseCodeInput.value.trim();
                let isValid = true;
                let errorMessage = '';
                
             // Only validate course name/code if we're on the add-course-form (not teacher form)
if (document.getElementById('add-course-form') === this && courseName && courseCode) {
    if (courseName.toLowerCase() === courseCode.toLowerCase()) {
        isValid = false;
        errorMessage = 'Course name and course code must be different';
    }
}
                
                // Only validate course type if we're on the add-course-form
if (document.getElementById('add-course-form') === this) {
    switch(courseType) {
        case 'UG':
            isValid = (numSemesters === 6 || numSemesters === 8);
            if (!isValid) errorMessage = 'UG courses must have 6 or 8 semesters';
            break;
        case 'PG':
            isValid = (numSemesters === 4);
            if (!isValid) errorMessage = 'PG courses must have 4 semesters';
            break;
        case 'UG+PG':
            isValid = (numSemesters === 10 || numSemesters === 12);
            if (!isValid) errorMessage = 'UG+PG courses must have 10 or 12 semesters';
            break;
        default:
            isValid = false;
            errorMessage = 'Please select a valid course type';
    }
}
                
                if (!isValid) {
                    event.preventDefault();
                    alert('Validation Error: ' + errorMessage);
                }
            });
            
            // Initialize on page load
            updateSemesterOptions();
        });
        
        // Add this after the existing document.getElementById('has-credits').addEventListener('change', function() {...})

        // Add validation for credit points based on subject type
        document.getElementById('subject-type').addEventListener('change', validateCreditPointsByType);
        document.getElementById('credit-points').addEventListener('input', validateCreditPointsByType);

        function validateCreditPointsByType() {
            const subjectType = document.getElementById('subject-type').value;
            const creditPointsInput = document.getElementById('credit-points');
            const hasCredits = document.getElementById('has-credits').value === '1';
            const creditPoints = parseInt(creditPointsInput.value);
            
            const errorDiv = creditPointsInput.parentElement.querySelector('.error-message') || 
                            createErrorDiv(creditPointsInput);
            
            // Only validate if has-credits is enabled
            if (!hasCredits) {
                hideError(errorDiv);
                return;
            }
            
            // Skip validation if subject type is not selected
            if (!subjectType) {
                return;
            }
            
            // Set validation rules based on subject type
            switch(subjectType) {
                case 'theory':
                    if (isNaN(creditPoints) || ![2, 3, 4].includes(creditPoints)) {
                        showError(errorDiv, 'Theory subjects can only have 2, 3, or 4 credit points');
                    } else {
                        hideError(errorDiv);
                    }
                    break;
                    
                case 'lab':
                    if (isNaN(creditPoints) || ![1, 2].includes(creditPoints)) {
                        showError(errorDiv, 'Lab subjects can only have 1 or 2 credit points');
                    } else {
                        hideError(errorDiv);
                    }
                    break;
                    
                case 'elective':
                    if (isNaN(creditPoints) || creditPoints !== 4) {
                        showError(errorDiv, 'Elective subjects must have exactly 4 credit points');
                    } else {
                        hideError(errorDiv);
                    }
                    break;
            }
        }

        // Update the credit-points input when subject type changes
        document.getElementById('subject-type').addEventListener('change', function() {
            const subjectType = this.value;
            const creditPointsInput = document.getElementById('credit-points');
            const hasCredits = document.getElementById('has-credits').value === '1';
            
            if (hasCredits && subjectType) {
                // Set default values based on subject type
                switch(subjectType) {
                    case 'theory':
                        creditPointsInput.value = '3';
                        break;
                        
                    case 'lab':
                        creditPointsInput.value = '2';
                        break;
                        
                    case 'elective':
                        creditPointsInput.value = '4';
                        break;
                }
                
                // Validate the default value
                validateCreditPointsByType();
            }
        });

        // Modify the existing credit-points event listener to use the new validation
        document.getElementById('has-credits').addEventListener('change', function() {
            const creditPointsGroup = document.getElementById('credit-points-group');
            if (this.value === '1') {
                creditPointsGroup.style.display = 'block';
                // Trigger validation based on current subject type
                validateCreditPointsByType();
            } else {
                creditPointsGroup.style.display = 'none';
            }
        });

        // Replace the existing form validation code with this scoped version
        document.addEventListener('DOMContentLoaded', function() {
            // Only run this code if we're on the Add Course form
            const addCourseForm = document.getElementById('add-course-form');
            if (addCourseForm) {
                const courseTypeSelect = document.getElementById('course-type');
                const numSemestersInput = document.getElementById('num-semesters');
                const courseNameInput = document.getElementById('course-name');
                const courseCodeInput = document.getElementById('course-code');

                // Form validation only for the Add Course form
                addCourseForm.addEventListener('submit', function(event) {
                    const courseType = courseTypeSelect.value;
                    const numSemesters = parseInt(numSemestersInput.value);
                    const courseName = courseNameInput.value.trim();
                    const courseCode = courseCodeInput.value.trim();
                    let isValid = true;
                    let errorMessage = '';
                    
                   
                
                    
                    // Validate based on course type
                    if (isValid) {
                        switch(courseType) {
                            case 'UG':
                                isValid = (numSemesters === 6 || numSemesters === 8);
                                if (!isValid) errorMessage = 'UG courses must have 6 or 8 semesters';
                                break;
                            case 'PG':
                                isValid = (numSemesters === 4);
                                if (!isValid) errorMessage = 'PG courses must have 4 semesters';
                                break;
                            case 'UG+PG':
                                isValid = (numSemesters === 10 || numSemesters === 12);
                                if (!isValid) errorMessage = 'UG+PG courses must have 10 or 12 semesters';
                                break;
                            default:
                                isValid = false;
                                errorMessage = 'Please select a valid course type';
                        }
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                        alert('Validation Error: ' + errorMessage);
                    }
                });
            }

            // Rest of your code for other forms...
        });
    </script>
</body>
</html>