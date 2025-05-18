<?php
// Set timeout limit for running tests
set_time_limit(300);

$output = "";
$returnCode = 0;

// Function to run pytest and capture output
function runPytest($testFile = "") {
    $command = "cd " . dirname(__FILE__) . " && python -m pytest ";
    
    if (!empty($testFile)) {
        $command .= $testFile . " -v";
    } else {
        $command .= " -v";  // Run all tests if no specific file
    }
    
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    
    $process = proc_open($command, $descriptorspec, $pipes);
    
    $output = "";
    $error = "";
    
    if (is_resource($process)) {
        fclose($pipes[0]);
        
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
    }
    
    return array(
        'output' => $output,
        'error' => $error,
        'returnCode' => $returnCode
    );
}

// Check if a specific test is requested
$testFile = isset($_GET['test']) ? $_GET['test'] : '';

// Run the test
$result = runPytest($testFile);
$output = nl2br($result['output']);
$error = nl2br($result['error']);
$returnCode = $result['returnCode'];

// Format the output for better readability
$success = $returnCode === 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRACULA - Python Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        pre {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        .success {
            color: green;
        }
        .failure {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4 text-center">BRACULA Python Tests</h1>
        
        <div class="card mb-4">
            <div class="card-header <?php echo $success ? 'bg-success' : 'bg-danger'; ?> text-white">
                <h3 class="mb-0">Test Results</h3>
            </div>
            <div class="card-body">
                <?php if (empty($output) && empty($error)): ?>
                    <div class="alert alert-warning">No test output generated. Make sure pytest is installed and configured correctly.</div>
                <?php else: ?>
                    <?php if (!empty($output)): ?>
                        <h4>Output:</h4>
                        <pre><?php echo $output; ?></pre>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <h4>Errors:</h4>
                        <pre class="failure"><?php echo $error; ?></pre>
                    <?php endif; ?>
                    
                    <div class="mt-3 <?php echo $success ? 'success' : 'failure'; ?>">
                        <strong>Exit Code: <?php echo $returnCode; ?></strong>
                        <p><?php echo $success ? 'All tests passed successfully!' : 'Some tests failed or encountered errors.'; ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">Back to Test Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 