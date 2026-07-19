<?php

include_once __DIR__. '/../helpers/functions.php';


include_once __DIR__. '/../models/UserModel.php';
include_once __DIR__. '/../models/CategoryModel.php';

include_once __DIR__. '/../models/RoleModel.php';

include_once __DIR__. '/../models/ContactModel.php';




$UserModel = new UserModel( $pdo);
$CategoryModel = new CategoryModel( $pdo);

$RoleModel = new RoleModel( $pdo);

$ContactModel = new ContactModel( $pdo);


