WP Expired Button
=============

About
-------
This plugin allows you to add a button to your posts that users can click to report an action. Specifically if a coupon is expired/not working. Each user is allowed 1 submission per post and is limited by IP. Once a post hits 10 submissions it is assign the 'Expired' tag. This limit is currently hard set at the top of the wp-expired-button.php defined by WPEB_SUBMISSION_COUNT_TO_EXPIRE which can be changed.

This plugin when activated creates the 'Expired' tag, the 'Expired Posts' submenu item under the 'Posts' menu, ability to use the [expired_button] shortcode within posts and the 'Expired Count' column on the 'All Posts' list.

To add the button to a post simply use the [expired_button] shortcode. This shortcode comes with the option of setting the buttons text.

  ex. [expired_button btn_text="Report Expired Deal"] outputs a HTML <button> tag with the inner text value of Report Expired Deal

The button can be styled via CSS in your theme's default CSS file. The buttons are assigned the class .expired_button

TODO
-------
* plugin settings page in admin
    - style button
    - change plugin defaults
* sortable custom posts column
* options to customize what to do when post hit's expired submission count
