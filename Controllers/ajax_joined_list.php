<?PHP
// SELECT department_name, Departments.department_id, join_users_departments.department_id from Departments (LEFT OR INNER) JOIN join_users_departments  on join_users_departments.department_id = departments.department_id
//				WHERE join_users_departments.users_id = 'x'

// SELECT  [?][ $disp_var ], [?].[$pkey_var], [$join_table].[$join_key] FROM Departments (Left or INNER) JOIN [$join_table] ON [$join_table].[$join_key] = [?].[$pkey_var]
//				WHERE [JOIN_TABLE].[$other_key] = '$session_val'

// Vars:
// $disp_table = Department
// $disp_var = [Will be visible in the dropdown] department_name
//  $disp_pkey = [will power the dropdown] Department_id
//
// $join_table =  join_departments_users
//   $join_key =   join_department_id
//   $other_key = join_user_id 
//
// $session_val = loaded user id as determined by the AcCombobox

// SECURITY : Determined by the Session Val, but what about "Loaded where clause" ? which is used to help other [static, etc] filters apply?
// Actually no, all we need is  


// to do: make default values on dropdowns work, other things? 
// to do: improve code beauty and coder-friendliness
// -- adding rows, deleting rows
// join tables (which can be hidden and function through a select multiple) [relies on adding, deleting rows]

// Process: 
//   -- Validate security
//   -- execute query
//   -- Display results


BEfore we begin, which of these complications still apply:

Filters 
- YES. IS NECESSARY.
- Now has been moved to a function

Request distinct
- Not really 

BUT WHY NOT JUST MERGE THIS ALL TO one file, how? 
?>