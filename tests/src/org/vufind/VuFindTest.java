package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.openqa.selenium.Dimension;
import org.openqa.selenium.WebDriver;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/21/13
 * Time: 10:06 AM
 */
public abstract class VuFindTest {
	public abstract void runTests(WebDriver driver, Ini configIni, TestResults results, Logger logger);

	protected void resizeToPhone(WebDriver driver){
		driver.manage().window().setSize(new Dimension(320, 480));
	}
	protected void resizeToLandscapePhone(WebDriver driver){
		driver.manage().window().setSize(new Dimension(480, 320));
	}
	protected void resizeToSmallTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(600, 800));
	}
	protected void resizeToLandscapeSmallTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(800, 600));
	}
	protected void resizeToTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(768, 1024));
	}
	protected void resizeToLandscapeTablet(WebDriver driver){
		driver.manage().window().setSize(new Dimension(1024, 768));
	}
	protected void resizeToFullDesktop(WebDriver driver){
		driver.manage().window().maximize();
	}
}
