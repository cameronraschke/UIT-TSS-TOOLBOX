import tkinter as tk
import mysql.connector

window = tk.Tk()

window.title("UIT-TSS-TOOLBOX Management")
window.rowconfigure(0, minsize=800, weight=1)
window.columnconfigure(1, minsize=800, weight=1)

cnx = mysql.connector.connect(
host="172.27.53.105",
user="management",
password="UHouston!",
database="laptopDB"
)


reply = tk.Text(window)
def cxn_query(sql):
    cursor = cnx.cursor()
    cursor.execute(sql)
    results = cursor.fetchall()
    reply.delete("1.0", tk.END)
    for (tagnumber) in results:
        print(tagnumber)
        reply.insert("1.0", tagnumber)
        reply.insert("1.0", "\n")
    cursor.close()
    cnx.close()

def cxn_connect():
    sql="SELECT 'bruh'"
    cxn_query(sql)

def cxn_q_all():
    sql="SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL GROUP BY tagnumber) AND tagnumber IS NOT NULL GROUP BY tagnumber"
    cxn_query(sql)


frm_buttons = tk.Frame(window, relief=tk.RAISED, bd=2)
btn_connect = tk.Button(frm_buttons, text="Connect...", command=cxn_connect)
btn_query = tk.Button(frm_buttons, text="Query", command=cxn_q_all())

btn_connect.grid(row=0, column=0, sticky="ew", padx=5)
btn_query.grid(row=1, column=0, sticky="ew", padx=5, pady=5)

frm_buttons.grid(row=0, column=0, sticky="ns")
reply.grid(row=0, column=1, sticky="nsew")

window.mainloop()

window.mainloop()



