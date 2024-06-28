import tkinter as tk
import datetime
import mysql.connector

window = tk.Tk()



def query_db():
    cnx = mysql.connector.connect(user='scott', database='employees')
    cursor = cnx.cursor()

    query = ("SELECT tagnumber, system_serial, time FROM jobstats "
            "WHERE tagnumber = %s AND %s")

    tagnumber = "625958"
    system_serial = "5CD014DJ2D"

    cursor.execute(query, (tagnumber, system_serial))

    for (first_name, last_name, hire_date) in cursor:
        print("{}, {} is in the DB".format(
        tagnumber, system_serial))

    cursor.close()
    cnx.close()

window.title("UIT-TSS-TOOLBOX Management")

window.rowconfigure(0, minsize=800, weight=1)
window.columnconfigure(1, minsize=800, weight=1)

txt_edit = tk.Text(window)
frm_buttons = tk.Frame(window, relief=tk.RAISED, bd=2)
btn_open = tk.Button(frm_buttons, text="Open")
btn_save = tk.Button(frm_buttons, text="Save As...")

window.rowconfigure(0, minsize=800, weight=1)
window.columnconfigure(1, minsize=800, weight=1)

btn_open.grid(row=0, column=0, sticky="ew", padx=5, pady=5)
btn_save.grid(row=1, column=0, sticky="ew", padx=5)

frm_buttons.grid(row=0, column=0, sticky="ns")
txt_edit.grid(row=0, column=1, sticky="nsew")

btn_open = tk.Button(frm_buttons, text="Open", command=query_db)
btn_save = tk.Button(frm_buttons, text="Save As...")

window.mainloop()