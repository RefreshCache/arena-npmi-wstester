=== Arena Web Services PHP Test Client ===
Author: Russell Todd (russell.todd@northpoint.org)
Version: 1.0


=== Description ===
This simple PHP script provides a way to invoke Arena web service API calls and display the resulting XML on a web page. You will need to know the syntax and parameters of the various API calls, which is typically available at /api.svc/help on your Arena installation. This script uses PHP server-side access to the web services so you can install it on any machine that can connect to your Arena environment.


=== Installation & Configuration ===
Deploy the two PHP files to any directory on your PHP-enabled web server.

Open up index.php and edit the $environments array starting on line 6. For each of your Arena environments you will need to know the following:

	•	The full URL of the API end point, typically http://my.arena.url/api.svc/
	•	The API Key found on the API Applications configuration page of the Arena install
	•	The API Secret found on the API Applications configuration page of the Arena install

Save the page and navigate to the folder you created. To invoke the WS on the first call you must login with your Arena credentials. Subsequent calls should use the session ID. A good first test is to run person/{ID} on yourself.


=== TODO ===
	•	Improve error and exception handling.

