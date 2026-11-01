@echo off
title T.S. Badminton Pattani - Cloudflare Live Tunnel
color 0A
cls
echo ===============================================================================
echo   T.S. Badminton Pattani - Cloudflare Live Public Tunnel
echo ===============================================================================
echo.
echo  [*] กำลังเชื่อมต่อระบบเข้ากับ Cloudflare Edge Network...
echo  [*] กรุณารอสักครู่ (ระบบจะแสดงลิงก์ https://...trycloudflare.com ด้านล่าง)
echo.
echo  [!] ข้อควรจำ:
echo      - อย่าเพิ่งปิดหน้าต่างดำนี้ขณะที่ให้เพื่อนทดสอบระบบ
echo      - ลิงก์ที่ได้ ให้นำไปต่อท้ายด้วย /ts-pattani/ เช่น:
echo        https://xxxx.trycloudflare.com/ts-pattani/
echo      - เมื่อทดสอบเสร็จแล้ว สามารถกดปิดหน้าต่างนี้เพื่อหยุดการแชร์ได้ทันที
echo.
echo ===============================================================================
echo.

C:\xampp\cloudflared.exe tunnel --url http://localhost:80

pause
