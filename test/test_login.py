import pytest
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


class TestLogin:
    def login_valid(self, setup):
        driver = setup
        driver.get('http://localhost:8081/BRACULA/html/login.html')  # Replace with the actual login URL
        email_input = driver.find_element(By.ID, "email")
        password_input = driver.find_element(By.ID, "password")
        login_button = driver.find_element(By.XPATH, "/html/body/div/form/button")

        email_input.send_keys("humayra.khan@g.bracu.ac.bd")  # Replace with the actual email
        password_input.send_keys("Humayra#123")  # Replace with the actual password
        login_button.click()
        time.sleep(2)  # Wait for the login to process
        # Check if the URL has changed to the expected URL after login
        assert driver.current_url == "http://localhost:8081/BRACULA/html/feed.html"  # Replace with the actual URL after login 
        time.sleep(2)  # Wait for the page to load
        # Wait for the login to complete and check for a successful login message
        try:
            WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.XPATH, "/html/body/div[2]/div[2]/div[1]/span"))
            )
            success_message = driver.find_element(By.XPATH, "/html/body/div[2]/div[2]/div[1]/span").text
            assert success_message == "Posts"
        except Exception as e:
            print(f"Login failed: {e}")
            assert False