# comment-assignment
Allows assigning Comments and Spoofing Comment Replies as  another User

Clone this Repo, download the [.ZIP of `master`](https://github.com/Py-Image/comment-assignment/archive/master.zip), or grab the [Latest Release (Recommended)](https://github.com/Py-Image/comment-assignment/releases/latest/) and you're good to go!

This will add two submenus under "Comments" in the WordPress dashboard: "Assigned to Me" and "Assigned to Others".

Comments can be assigned using the "Quick Edit" screen for a Comment, or the full-blown Edit screen.

![image](https://user-images.githubusercontent.com/7770631/32912835-51f3fd70-cade-11e7-9081-c1172f05684e.png)

This will cause the Comment to show for them under their own "Assigned to Me" screen. The "Assigned to Others" screen does the opposite and only shows Comments Assigned to Other Users. The default "Comments" screen is the only place unassigned comments will show.

Replying as other Users can also be done from these screens. Just click the "Reply" link and a dropdown of Users will appear next to the Submit button.
This defaults to User ID "1", but it can either be changed to another one before Full Release or it can be changed using a Filter.

![image](https://user-images.githubusercontent.com/7770631/32912927-a5cfa4f8-cade-11e7-8765-bfa442ccf574.png)

The Dropdowns for both Assignment and Replying As will only show Users with the `edit_posts` cabability, which is the capability WordPress checks both to display the "Comments" dashboard item as well as for Replying to Comments from the dashboard.
