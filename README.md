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
