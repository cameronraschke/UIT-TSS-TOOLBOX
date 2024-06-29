import tkinter as tk
import mysql.connector

window = tk.Tk()

window.title("UIT-TSS-TOOLBOX Management")

cnx = mysql.connector.connect(
host="172.27.53.105",
user="management",
password="UHouston!",
database="laptopDB"
)

cursor = cnx.cursor()
cursor.execute("SELECT tagnumber FROM jobstats limit 20")
results = cursor.fetchall()

reply = tk.Text(window)
reply.pack()
#reply.configure(state="readonly")
# for i in results:
#     print(i)
#     reply.insert(0, i)
reply.insert(0, "bruh")
reply.pack()
cursor.close()
cnx.close()

window.mainloop()