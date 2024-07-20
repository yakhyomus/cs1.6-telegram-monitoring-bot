# Simple Server Monitoring Bot for Telegram
This is a simple server monitoring bot for Telegram written in PHP. It displays information about all servers and can send map screenshots (uploaded by you). If added to a group and granted admin rights, the bot will also work in the group (and can greet new members).

# Features
Displays information about all servers.
Sends map screenshots (uploaded by you).
Greets new members when added to a group with admin rights.
Setup
Replace the placeholder values in the code with your actual data:

Bot Token: Replace "YOUR_TELEGRAM_BOT_TOKEN" with your bot's token.
Domain URL: Update the $url variable with your domain URL (must use https://).
Map Screenshots Folder: Specify the path to your map screenshots folder in the $folder variable.
Server List: Add your server addresses to the $servers array.
Example Configuration
```
// Replace with your bot's token
$TOKEN  = "YOUR_TELEGRAM_BOT_TOKEN";

// Link to your domain without a trailing slash (! protocol https:// is mandatory !)
$url    = 'https://example.com';

// Folder with map screenshots (write the path to the folder here, not above)
$folder = '/maps/';

// Enter your server list
$servers = [
    '/public'   => '192.168.0.1:27015',
    '/server'   => '192.168.0.1:27016',
    '/csdm'     => '192.168.0.1:27017',
];
```
# Usage
To display server information, send the command /info in the chat with the bot.
To display information for a specific server, use the server key (e.g., /public, /server, /csdm).
Adding the Bot to a Group
Add the bot to your Telegram group.
Grant the bot admin rights in the group.
The bot will greet new members and respond to commands within the group.

![ezgif-3-e2d15ac29b](https://github.com/user-attachments/assets/ab0f14fc-2c3e-4698-86c1-415dcb5eb361)
