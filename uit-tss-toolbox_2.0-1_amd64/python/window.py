import tkinter as tk
import mysql.connector

result = "Nada"
def query_db():
    cnx = mysql.connector.connect(
    host="172.27.53.105",
    user="management",
    password="UHouston!",
    database="laptopDB"
    )
    cursor = cnx.cursor()

    query = ("SELECT tagnumber, system_serial, time FROM jobstats "
            "WHERE tagnumber = %s AND %s")

    tagnumber = "625958"
    system_serial = "5CD014DJ2D"

    cursor.execute(query, (tagnumber, system_serial))

    for (first_name, last_name, hire_date) in cursor:
        result = print("{}, {} is in the DB".format(
        tagnumber, system_serial))

    cursor.close()
    cnx.close()

window = tk.Tk()

window.title("UIT-TSS-TOOLBOX Management")

window.rowconfigure(0, minsize=800, weight=1)
window.columnconfigure(1, minsize=800, weight=1)

results = tk.Label(window, text=result)
frm_buttons = tk.Frame(window, relief=tk.RAISED, bd=2)
btn_query = tk.Button(frm_buttons, text="Query DB")
btn_save = tk.Button(frm_buttons, text="Save As...")

window.rowconfigure(0, minsize=800, weight=1)
window.columnconfigure(1, minsize=800, weight=1)

btn_query.grid(row=0, column=0, sticky="ew", padx=5, pady=5)
btn_save.grid(row=1, column=0, sticky="ew", padx=5)

frm_buttons.grid(row=0, column=0, sticky="ns")
results.grid(row=0, column=1, sticky="nsew")

btn_query = tk.Button(frm_buttons, text="Query DB", command=query_db)
btn_save = tk.Button(frm_buttons, text="Save As...")

window.mainloop()