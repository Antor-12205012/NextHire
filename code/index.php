<?php
// NextHire - Main Landing Page

require_once __DIR__ . '/header.php';

// Fetch overall platform statistics from database if available
$total_companies = 0;
$total_jobs = 0;
$total_applicants = 0;

try {
    $company_stmt = $pdo->query("SELECT COUNT(*) FROM companies");
    $total_companies = $company_stmt->fetchColumn();
    
    $jobs_stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
    $total_jobs = $jobs_stmt->fetchColumn();
    
    $cand_stmt = $pdo->query("SELECT COUNT(*) FROM candidates");
    $total_applicants = $cand_stmt->fetchColumn();
} catch (Exception $e) {
    // Suppress errors and use default zeros
}
?>