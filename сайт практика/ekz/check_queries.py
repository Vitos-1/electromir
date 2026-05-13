
import subprocess

def run_mysql(query):
    cmd = ['mysql', '-u', 'root', '-p12345', '-D', 'Library', '-t', '-e', query]
    result = subprocess.run(cmd, capture_output=True, text=True, encoding='utf-8')
    if result.stdout:
        print(result.stdout)
    if result.stderr:
        print(f"Error: {result.stderr}")

queries = [
    "SELECT '1) Authors on different shelves' AS '';",
    "SELECT DISTINCT Автор.ФИО FROM Автор JOIN Расстановка ON Автор.Номер = Расстановка.Номер_автора GROUP BY Автор.ФИО, Автор.Номер HAVING COUNT(DISTINCT Расстановка.Шифр_полки) > 1;",
    "SELECT '2) Shelves with books from 1833' AS '';",
    "SELECT DISTINCT Полка.Название FROM Полка JOIN Расстановка ON Полка.Шифр = Расстановка.Шифр_полки JOIN Книга ON Расстановка.Номер_книги = Книга.Номер WHERE Книга.Год_издания = 1833;",
    "SELECT '3) Author with most books' AS '';",
    "SELECT Автор.ФИО FROM Автор JOIN Расстановка ON Автор.Номер = Расстановка.Номер_автора GROUP BY Автор.ФИО, Автор.Номер ORDER BY SUM(Расстановка.Количество) DESC LIMIT 1;",
    "SELECT '4) City of author with thickest book on shelf P1' AS '';",
    "SELECT Автор.Город_проживания FROM Автор JOIN Расстановка ON Автор.Номер = Расстановка.Номер_автора JOIN Книга ON Расстановка.Номер_книги = Книга.Номер WHERE Расстановка.Шифр_полки = 'П1' ORDER BY Книга.Количество_страниц DESC LIMIT 1;",
    "SELECT '5) Authors not on shelf P3' AS '';",
    "SELECT ФИО FROM Автор WHERE Номер NOT IN (SELECT DISTINCT Номер_автора FROM Расстановка WHERE Шифр_полки = 'П3');",
    "SELECT '6) List of publishers' AS '';",
    "SELECT DISTINCT Издательство FROM Книга;"
]

for q in queries:
    run_mysql(q)
