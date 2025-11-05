# Quick Setup Guide - Delivery Companies Plugin

## ✅ **Issue Fixed: User Selection Problem**

The issue was that the admin interface was only showing users with the 'customer' role, but you assigned the 'delivery_company' role to your user.

### **🔧 What I Fixed:**

1. **Updated User Selection**: Now shows users with both 'customer' and 'delivery_company' roles
2. **Prevents Duplicates**: Users who already have a delivery company profile won't appear in the dropdown
3. **Role Indicators**: Shows `[Delivery Company]` next to users with that role
4. **Better Instructions**: Added clear step-by-step instructions
5. **Quick Links**: Added buttons to create new users or manage existing ones

### **📋 How to Add a Delivery Company Now:**

#### **Option 1: Use Your Existing User**
1. Go to **WordPress Admin > Delivery Companies > Add Company**
2. In the "User Account" dropdown, you should now see your user with `[Delivery Company]` next to their name
3. Select your user and fill in the company details
4. Click "Add Company"

#### **Option 2: Create a New User**
1. Go to **WordPress Admin > Users > Add New**
2. Create a new user with:
   - Username: `delivery-company-1` (or any unique username)
   - Email: `company@example.com`
   - Password: Choose a strong password
   - Role: **Delivery Company**
3. Save the user
4. Go back to **Delivery Companies > Add Company**
5. Select the new user from the dropdown
6. Fill in the company details and save

### **🎯 What You'll See:**

- **User Dropdown**: Now shows all eligible users
- **Role Indicators**: `[Delivery Company]` shows which users have that role
- **Instructions**: Clear step-by-step guide at the top
- **Quick Links**: Buttons to create users or manage existing ones
- **No Duplicates**: Users with existing company profiles are excluded

### **✅ Next Steps After Adding Company:**

1. **Activate Company**: Change status from "Pending" to "Active"
2. **Set Up Shipping Zones**: Company can add their service areas and rates
3. **Test the System**: Create a test order to see the delivery company integration

### **🔍 Troubleshooting:**

- **Still don't see your user?** Make sure they have either 'customer' or 'delivery_company' role
- **User already has company?** They won't appear in the dropdown (prevents duplicates)
- **Need to change user role?** Go to Users > All Users > Edit User > Role

The plugin is now ready to use! 🚀

