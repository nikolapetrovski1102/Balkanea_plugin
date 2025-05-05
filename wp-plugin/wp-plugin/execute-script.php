<?php 

try {
    // Define the path to your Python executable and the script
    $pythonExecutable = __DIR__ . '/python-script/Python-3.12.8/Programs/python.c'; // Adjust this path if needed
    $localPath = __DIR__ . '/python-script/main.py';

    // Check if the Python executable exists
    if (!file_exists($pythonExecutable)) {
        throw new Exception("Python executable not found at $pythonExecutable");
    }

    // Check if the Python script exists
    if (!file_exists($localPath)) {
        throw new Exception("Python script not found at $localPath");
    }

    // Construct the command to execute the Python script
    $command = escapeshellcmd("$pythonExecutable $localPath") . " 2>&1"; // Redirect stderr to stdout

    // Execute the command
    $output = shell_exec($command);

    if ($output === null) {
        throw new Exception("Failed to execute the Python script");
    }

    // Display the output of the script
    echo "Script Output: \n$output";
} catch (\Exception $ex) {
    // Display the error message
    echo "Error: " . $ex->getMessage();
}

?>
