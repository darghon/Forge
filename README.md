# Forge
Forge is a custom MVC framework for PHP. Compatible with PHP 5.3 and up.

1. Getting started
--
To start using this framework you first download this repository, and place it in a folder structure: <projectroot>/ext/forge
Next you open up your commandline, and navigate to the forge folder.
Type in the command: 

    php forge build project

The framework will now build the basic structure of a new forge project.
Next up, you will want to start a new application and modules. These can be added either manually in the app folder, or by typing the following commands:

    php forge build application <app_name>
    php forge build module <app_name> <module_name> <?action_1> <?action_2> ...

These applications are placed in the <projectroot>/app location.
This location will always contain a "shared" folder. This folder will, by default, contain any and all shared resources between the applications within the project.

2. Setting up database
--
The database support is currently only for MySql, but can be created using Yaml files.
The connection information for your databases is contained within the config folder. 

This configuration file is prefilled to help you complete the required information.
You are able to add multiple environments within this file.

Next up you can make your database structure.
This again is made by Yaml files in the <projectroot>/config/database folder

This folder contains an example yaml file to guide you through the structure of creating your tables and relations.
Keep in mind that the framework will add primary keys, version numbers, delete flags and foreign keys where needed.

Once you are done with the configuration you can tell the framework to generate the objects and the database with the following commands:

    php forge build objects
    php forge build database <environment>
    
The framework will create 6 files for each database table. These files are split up into 3 layers, with each a base class and a editable class.
Re-building the objects will overwrite all changes to the base files if they exists, but will keep the normal classes as-is.

The layers consist of a data layer in the \Data\ namespace. These files contain the basic data validation of the table records.
The next layer is the finder layer in the \Finder\ namespace. These files contain all the retrieval data and queries to retrieve data from, or persist it to the database.
Lastly there is the business layer in the global namespace. These files contain all the getters and setters for each of the properties of the data object.
Normally, you only use these business objects in your code.

Basic usage of the objects:

    /* New User */
    $user = new \User();
    $user->setName('My name');
    $user->persist();
    
    /* Existing User */
    $new_user = &\User::Find($user->getId()); /* &\User::Find() returns a instance of the finder layer for this object */
    echo $new_user->getName();
    
The $new_user variable will contain a reference to the first user.
