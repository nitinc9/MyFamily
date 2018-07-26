# Introduction

A Facebook web application to build stronger connections by knowing your family better. In this digital age, social media plays a key role to bridge distances so that family members can not just know each others names, but know more about them. What was it like when they were growing up? What are their hobbies? May be the grandson and grandparent are both die-hard movie lovers. Discover interesting facts about your family and build stronger connections.

**Note: The app is currently in Development Mode.**

# Features

- **Edit basic information** about the user, such as, date of birth, location, etc.
- **Add/Edit/Delete Family**. Create multiple families for better organization and ease of sharing.
- **Add/Edit/Delete Family Members**.
- Family Members can add interesting facts about them by answering simple questions, such as, what was it like when they were growing up? Share some interesting facts so your distant family members can also know more about you.
  - Family Member facts can only be seen by that specific family members. That is, if a member is part of two families, member from family one will not be able to see the facts shared with the other family. 
- View your **Family Tree**.
- Role-based Access Control
  - **Family Admin**: Can do all operations on a family. This is controlled by the **Is family admin** permission. A creator is automatically a Family Admin.
  - **Family Manager**: Can do all operations on a family, except editing and deleting the family itself. This is controlled by the **Can manage family** permission.
  - **Normal Member**: Can update their basic information and add facts about themselves.

# Getting Started

## Pre-requisites

### Facebook User Permissions
In order to use the app, please use the following permissions list.
- Must have
  - friends
- Nice to have (but, highly recommended)
  - gender
  - location
  - birthday 

## Application URL
https://apps.facebook.com/cloudnine-my-family
or
https://apps.facebook.com/233351993945655

## How to use the App?
Use the **My Family** app to know your family better and build stronger connections by following these simple steps.
- **Invite** family members to use the **My Family** app.
- **Create a family**. Don't forget to give it a cool name! For example, *The Legendary Patils*.
  - __Tip__: You can create multiple families to organize better and sshhhh... keep family secrets.
- **Add members** to your family. You can also enable few members to manage the family.
- **Add interesting facts** about each member, such as, what was it like when they were growing up? What are their favorite movies?
  - __Tip__: Only family members can see these details. So, feel free to share.
- View your awesome **Family Tree**.

Keep adding more details to your family and build stronger connections!

## Demo URL
https://youtu.be/Go4RbO5r5b8

## Screenshots

### Add Family

![Add Family](/images/screenshots/Add_Family.png?raw=true "Add Family")

### Add Member

![Add Member](/images/screenshots/Add_Member.png?raw=true "Add Member")

### Manage Members

![Manage Members](/images/screenshots/Manage_Members.png?raw=true "Manage Members")

### Add Facts About a Member

![Member Responses](/images/screenshots/Member_Responses.png?raw=true "Member Responses")

### View Family Tree

![Family Tree](/images/screenshots/User_Family_Tree.png?raw=true "Family Tree")

### View Member Information

![Member Information](/images/screenshots/Member_Info.png?raw=true "Member Information")

# Understanding the Code

The app uses the following files as the entry points.
- `index.php`: The launching page of the application that puts down the main layout.
- `api.php`: Used for the AJAX calls.
- `ui.php`: Used to render a section of the UI and give a more cohesive user-experience.

The code uses the following classes for the main logic.
- `MyFamily`: Acts as the controller.
- `MyFamilyDelegate`: Provides the core business logic.
- `MyFamilyUI`: Provides the UI related logic.

# Configuration

Application's configuration is managed via `config.json`.

# Troubleshooting

The application generates server-side logs and log level is configurable in the `config.json`.

# Other Info
Facebook Hackathon URL: https://devpost.com/software/myfamily

# Credits

- [icons8.com](https://icons8.com)
  - For images in the logo
  - For profile images
  - For loading spinner gif
- [Treant.js](http://fperucic.github.io/treant-js)
  - For the awesome chart library
