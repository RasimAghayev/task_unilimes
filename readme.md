# PHP Test Task

1. You need to upload the project to a public repository in gitlab
2. The test project must be run in docker
3. You need to create a readme page about starting and using the project

## Develop a service for working with a dataset

4. Initial data: .csv dataset

[`data.txt`](https://drive.google.com/file/d/1Dwb1alDAQCAPwz7Eg306BVbWtGdfkUCy/view?usp=sharing)

``

     'category', // client's favorite category
     'firstname',
     'lastname',
     'email',
     'gender',
     'birthDate'
``

5. Without using third party libraries: Read csv file.

6. Write the received data to the database.

7. Display data as a table with pagination (but you can also use a simple json api)

8. Implement filters by values:
     category
     gender
     Date of Birth
     age
     age range (for example, 25 - 30 years)

9. Implement data export (in csv) according to the specified filters.


