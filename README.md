# Email Exploder List

## Overview

This application pulls your companies email distribution lists from Google and Active Directory and displays in a web page.

This application is a bit specific to my companies setup, but can be used as a good starting point when developing for your own company.

![alt tag](https://github.com/jaredeckersley/emailExploder/blob/master/emailExploder.png)


```
For my setup, there are 2 types of groups - Google managed and AD managed.  

The groups that are Google managed are listed in AD in a Listserver Data OU.  Both groups have their membership info pulled from Google but only the Google groups can be subscribed to.

```

```
Here is how you can view all your groups via Google:
https://groups.google.com/a/yourdomain.com/forum/?hl=en#!forumsearch/

Example of a group membership list:
https://groups.google.com/a/yourdomain.com/forum/?hl=en#!members/announcements

Example of group join page:
There is a "Join group" button on the member list page, but you can try this URL:
https://groups.google.com/a/yourdomain.com/d/forum/yourlist/subscribe
https://groups.google.com/a/yourdomain.com/d/forum/yourlist/join
https://groups.google.com/a/yourdomain.com/d/forum/yourlist/unsubscribe

Another way to subscribe to one of your companies Google controlled groups is to
send an email to the group address like this: desiredGroup+subscribe@yourdomain.com
```