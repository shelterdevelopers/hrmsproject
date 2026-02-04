# Railway Setup - Connect Database to Your App

Your MySQL and web app are separate services. The app needs the database credentials. Add them like this:

## Option A: Variable reference (recommended)

1. In Railway dashboard, click your **web app service** (the one with your PHP code)
2. Go to **Variables** tab
3. Click **"+ New Variable"** or **"Add Variable Reference"**
4. Choose **"Variable Reference"** (reference another service's variable)
5. Select your **MySQL service** from the dropdown
6. Add these references:
   - **MYSQL_URL** (this one has everything: host, user, password, database)
   - OR all of: **MYSQLHOST**, **MYSQLUSER**, **MYSQLPASSWORD**, **MYSQLDATABASE**, **MYSQLPORT**
7. Click **Deploy** / **Redeploy** (the variables apply on next deploy)

## Option B: Manual paste (if references don't work)

1. Click your **MySQL service** → **Variables**
2. Find **MYSQL_URL** → click **Reveal** / **Show** to see the full connection string
3. Copy it (looks like `mysql://root:xxxxx@trolley.proxy.rlwy.net:59231/railway`)
4. Click your **web app service** → **Variables**
5. Click **"+ New Variable"**
6. Name: `MYSQL_URL`
7. Value: paste the connection string you copied
8. **Deploy**

---

After adding variables, push your code and redeploy:
```bash
git add .
git commit -m "Railway config"
git push
```
