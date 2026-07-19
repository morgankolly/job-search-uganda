<?php

if (isset($_POST["insertCategory"]) && !empty($_POST["category_name"])) {
    $categoryName = $_POST["category_name"];
    $CategoryModel->insertCategory($categoryName);
} 
 

    $categories= $CategoryModel->fetchAllCategories();
