import pytest
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

class TestResources:
    def resources_page(self, setup):
        driver = setup
        driver.get("http://localhost:8081/BRACULA/html/resources.html")
        time.sleep(2)

        resources_button = driver.find_element(By.XPATH, "/html/body/div[1]/div[2]/div[2]/button[1]")
        resources_button.click()
        time.sleep(2)

        file_input = driver.find_element(By.ID, "file")
        file_path = r"C:\Users\Rafi\Downloads\Cse470(Features final).docx"
        file_input.send_keys(file_path)

        courseid = driver.find_element(By.ID, "courseCodeUpload")
        courseid.send_keys("CSE422")

        time.sleep(1)
        driver.find_element(By.ID, "semesterUpload").click()
        driver.find_element(By.XPATH, "/html/body/div[2]/div/form/div[3]/select/option[2]").click()

        time.sleep(1)
        driver.find_element(By.ID, "materialType").click()
        driver.find_element(By.XPATH, "/html/body/div[2]/div/form/div[4]/select/option[2]").click()

        upload_button = driver.find_element(By.XPATH, "/html/body/div[2]/div/form/button")
        upload_button.click()
        time.sleep(2)

        # Handle alert popup here
        try:
            WebDriverWait(driver, 5).until(EC.alert_is_present())
            alert = driver.switch_to.alert
            print(f"Alert text: {alert.text}")
            assert alert.text == "Material uploaded successfully!"
            alert.accept()
        except Exception as e:
            print(f"No alert appeared or error occurred: {e}")
            assert False  # Mark test as failed if alert not handled properly

        time.sleep(2)
    def download_resources(self, setup):
        driver = setup
        # Download the file
        download_button = driver.find_element(By.XPATH, "/html/body/div[1]/div[2]/div[3]/div/div[2]/div[2]/button[1]")
        download_button.click()
        time.sleep(2)
    # Check search functionality
    def search_resources(self, setup):
        driver = setup
        search_input = driver.find_element(By.ID, "courseCode")
        search_input.send_keys("BUS")

        filter_button = driver.find_element(By.XPATH, "/html/body/div[1]/div[1]/button")
        filter_button.click()
        time.sleep(4)