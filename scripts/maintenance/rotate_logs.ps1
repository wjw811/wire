# Log rotation script
$LogDir = "logs"
$MaxLines = 1000

Write-Host "Checking log file sizes..."

$logFiles = @()
$logFiles += Get-ChildItem -Path $LogDir -Filter "*.out" -File
$logFiles += Get-ChildItem -Path $LogDir -Filter "*.err" -File

foreach ($file in $logFiles) {
    $lineCount = (Get-Content $file.FullName | Measure-Object -Line).Lines
    
    if ($lineCount -gt $MaxLines) {
        Write-Host "File $($file.Name) has $lineCount lines, exceeding limit $MaxLines, rotating..."
        
        $lastLines = Get-Content $file.FullName -Tail $MaxLines
        $lastLines | Out-File -FilePath $file.FullName -Encoding UTF8
        
        Write-Host "Rotated file $($file.Name), kept last $MaxLines lines"
    } else {
        Write-Host "File $($file.Name) has $lineCount lines, no rotation needed"
    }
}

Write-Host "Log rotation completed"