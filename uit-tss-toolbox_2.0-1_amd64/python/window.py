import tkinter as tk
import mysql.connector
from mysql.connector import errorcode

window = tk.Tk()

window.title("UIT-TSS-TOOLBOX Management")
window.rowconfigure(0, minsize=800, weight=1)
window.columnconfigure(1, minsize=800, weight=1)


reply = tk.Text(window)

def cxn_query(stmt):
    cxn = mysql.connector.connect(
    host="172.27.53.105",
    user="management",
    password="UHouston!",
    database="laptopDB"
    )
    cursor = cxn.cursor()

    cursor.execute(stmt)
    results = cursor.fetchall()
    reply.delete("1.0", tk.END)
    nlines = 0
    for (tagnumber) in results:
        nlines = nlines + 1
        print(tagnumber)
        reply.insert("1.0", tagnumber)
        reply.insert("1.0", "\n")
    reply.insert("1.0", f'Number of lines: {nlines}')
    reply.insert("1.0", "\n")
    cursor.close()
    cxn.close()

def cxn_update(stmt):
    cxn = mysql.connector.connect(
    host="172.27.53.105",
    user="management",
    password="UHouston!",
    database="laptopDB"
    )
    cursor = cxn.cursor()

    cursor.execute(stmt)
    cxn.commit()
    cursor.close()
    cxn.close()


def cxn_connect():
    stmt="SELECT 'Connected to 172.27.53.105'"
    cxn_query(stmt)

def cxn_q_all():
    stmt="SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL GROUP BY tagnumber) AND tagnumber IS NOT NULL GROUP BY tagnumber"
    cxn_query(stmt)

def cxn_update_b():
    stmt="UPDATE remote SET task = 'update' WHERE tagnumber IN (SELECT location FROM locations WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND location = 'b'"
    cxn_update(stmt)


frm_buttons = tk.Frame(window, relief=tk.RAISED, bd=2)
btn_connect = tk.Button(frm_buttons, text="Connect...", command=cxn_connect)
btn_query = tk.Button(frm_buttons, text="Query", command=cxn_q_all)
btn_update_b = tk.Button(frm_buttons, text="Update Loc B", command=cxn_update_b)

btn_connect.grid(row=0, column=0, sticky="ew", padx=5)
btn_query.grid(row=1, column=0, sticky="ew", padx=5, pady=5)
btn_update_b.grid(row=2, column=0, sticky="ew", padx=5, pady=5)

frm_buttons.grid(row=0, column=0, sticky="ns")
reply.grid(row=0, column=1, sticky="nsew")

window.mainloop()
