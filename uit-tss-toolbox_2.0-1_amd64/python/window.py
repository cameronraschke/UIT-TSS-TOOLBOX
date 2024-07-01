import tkinter as tk
import mysql.connector
from mysql.connector import errorcode

window = tk.Tk()

window.title("UIT-TSS-TOOLBOX Management")
window.rowconfigure(0, minsize=800, weight=1)
window.columnconfigure(1, minsize=800, weight=1)

serverIP="172.27.53.105"


reply = tk.Text(window)

def cxn_query(stmt, info):
    global serverIP
    cxn = mysql.connector.connect(
    host=serverIP,
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
    reply.insert("1.0", "\n")
    reply.insert("1.0", info)
    reply.insert("1.0", "\n")
    reply.insert("1.0", f'Number of lines selected: {nlines}')
    cursor.close()
    cxn.close()


def cxn_query_table(stmt, info):
    global serverIP
    cxn = mysql.connector.connect(
    host=serverIP,
    user="management",
    password="UHouston!",
    database="laptopDB"
    )
    cursor = cxn.cursor()

    cursor.execute(stmt)
    reply.delete("1.0", tk.END)
    nlines = 0
    for heading in [i[0] for i in cursor.description]:
        nlines = nlines + 1
        print(heading)
        for result in cursor.fetchone():
            reply.insert("1.0", result)
            reply.insert("1.0", heading)
            reply.insert("1.0", "\n")
    reply.insert("1.0", "\n")
    reply.insert("1.0", info)
    reply.insert("1.0", "\n")
    reply.insert("1.0", f'Number of lines selected: {nlines}')
    cursor.close()
    cxn.close()

def cxn_update(stmt, info):
    global serverIP
    cxn = mysql.connector.connect(
    host=serverIP,
    user="management",
    password="UHouston!",
    database="laptopDB"
    )
    cursor = cxn.cursor()

    reply.delete("1.0", tk.END)
    cursor.execute(stmt)
    cxn.commit()
    reply.insert("1.0", "\n")
    reply.insert("1.0", f'{info}')
    reply.insert("1.0", "\n")
    reply.insert("1.0", f'Number of lines updated: {cursor.rowcount}')
    cursor.close()
    cxn.close()


def cxn_connect():
    stmt="call selectRemoteStats()"
    info="Testing connection to the host."
    cxn_query_table(stmt, info)

def cxn_query_all():
    stmt="SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL GROUP BY tagnumber) AND tagnumber IS NOT NULL GROUP BY tagnumber"
    info="Selecting all laptops in the database."
    cxn_query(stmt, info)

def cxn_update_b():
    stmt="UPDATE remote SET task = 'update' WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND location = 'b')"
    info="Updating all laptops in box 'b'"
    cxn_update(stmt, info)

def cxn_update_q():
    stmt="UPDATE remote SET task = 'update' WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND location = 'q')"
    info="Updating all laptops in box 'q'"
    cxn_update(stmt, info)

def cnx_update_all():
    stmt="UPDATE remote SET task = 'update' WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND location LIKE '%')"
    info="Updating all laptops."
    cxn_update(stmt, info)

def cnx_update_clear():
    stmt="UPDATE remote SET task = NULL WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND location LIKE '%')"
    info="Clearing all tasks for all laptops."
    cxn_update(stmt, info)

frm_buttons = tk.Frame(window, relief=tk.RAISED, bd=2)

btn_connect = tk.Button(frm_buttons, text="Test Conn", command=cxn_connect)
btn_query = tk.Button(frm_buttons, text="Query All", command=cxn_query_all)
btn_update_b = tk.Button(frm_buttons, text="Update Loc B", command=cxn_update_b)
btn_update_q = tk.Button(frm_buttons, text="Update Loc Q", command=cxn_update_q)
btn_update_all = tk.Button(frm_buttons, text="Update All", command=cnx_update_all)
btn_clear_all = tk.Button(frm_buttons, text="Clear All Tasks", command=cnx_update_clear)

btn_connect.grid(row=1, column=0, sticky="ew", padx=5)
btn_query.grid(row=2, column=0, sticky="ew", padx=5, pady=5)
btn_update_all.grid(row=3, column=0, sticky="ew", padx=5, pady=5)
btn_clear_all.grid(row=4, column=0, sticky="ew", padx=5, pady=5)
btn_update_b.grid(row=5, column=0, sticky="ew", padx=5, pady=5)
btn_update_q.grid(row=6, column=0, sticky="ew", padx=5, pady=5)

frm_buttons.grid(row=0, column=0, sticky="ns")
reply.grid(row=0, column=1, sticky="nsew")

window.mainloop()
