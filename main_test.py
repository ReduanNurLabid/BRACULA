import pytest
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
# from utils.config import Config
from test_login import TestLogin
from test_resources import TestResources
from test_feeds import TestFeeds


@pytest.fixture(scope="module")
def setup():
    # Initialize the WebDriver
    driver = webdriver.Chrome()
    driver.maximize_window()
    driver.implicitly_wait(10)
    # driver.get(Config.BASE_URL)
    yield driver
    

# Login with valid credentials
def test_login(setup):
    user_login = TestLogin()
    user_login.login_valid(setup)

#Test the Resources page
def test_resources_page(setup):
     upload_resources = TestResources()
     upload_resources.resources_page(setup)
#Test the download functionality
def test_download_resources(setup):
     download_resources = TestResources()
     download_resources.download_resources(setup)
# Test the search functionality
# def test_search_resources(setup):
     search_resources = TestResources()
     search_resources.search_resources(setup)
#Test the Feeds page
def test_feeds_page(setup):
     feeds = TestFeeds()
     feeds.feeds_page(setup)

#Test the comment functionality
def test_comment_on_post(setup):
    comment = TestFeeds()
    comment.comment_on_post(setup)