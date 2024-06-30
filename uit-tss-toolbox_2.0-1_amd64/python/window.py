import tkinter as tk
import mysql.connector

window = tk.Tk()

window.title("UIT-TSS-TOOLBOX Management")

cnx = mysql.connector.connect(
host="10.10.0.2",
user="management",
password="UHouston!",
database="laptopDB"
)

cursor = cnx.cursor()
cursor.execute("SELECT tagnumber, time FROM jobstats WHERE time IN (SELECT max(time) from jobstats group by tagnumber) ORDER BY time ASC limit 20")
results = cursor.fetchall()

reply = tk.Text(window)
reply.pack()
for (i) in results:
    reply.insert("1.0", i)
    reply.insert("1.0", "\n")
    reply.pack()

cursor.close()
cnx.close()

window.mainloop()