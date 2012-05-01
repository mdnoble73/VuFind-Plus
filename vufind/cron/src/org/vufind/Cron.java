package org.vufind;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Date;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;

public class Cron {

	private static Logger logger = Logger.getLogger(Cron.class);
	private static String serverName;
	
	private static Connection vufindConn;
	private static Connection econtentConn;

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		if (args.length == 0){
			System.out.println("The name of the server to run cron for must be provided as the first parameter.");
			System.exit(1);
		}
		serverName = args[0];
		args = Arrays.copyOfRange(args, 1, args.length);
		
		Date currentTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.cron.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(currentTime.toString() + ": Starting Cron");
		// Setup the MySQL driver
		try {
			// The newInstance() call is a work around for some
			// broken Java implementations
			Class.forName("com.mysql.jdbc.Driver").newInstance();

			logger.info("Loaded driver for MySQL");
		} catch (Exception ex) {
			logger.info("Could not load driver for MySQL, exiting.");
			return;
		}

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = new Ini();
		File configFile = new File("../../sites/" + serverName + "/conf/config.ini");
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.");
		} catch (FileNotFoundException e) {
			logger.error("Configuration file (" + configFile.getPath() + ") could not be found.  You must supply a configuration file in conf called config.ini.");
		} catch (IOException e) {
			logger.error("Configuration file could not be read.");
		}
		
		//Connect to the database
		String databaseConnectionInfo = Util.cleanIniValue(ini.get("Database","database_vufind_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("VuFind Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return;
		}
		String econtentConnectionInfo = Util.cleanIniValue(ini.get("Database","database_econtent_jdbc"));
		if (econtentConnectionInfo == null || econtentConnectionInfo.length() == 0) {
			logger.error("eContent Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return;
		}
		
		try {
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + databaseConnectionInfo, ex);
			return;
		}
		try {
			econtentConn = DriverManager.getConnection(econtentConnectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + econtentConnectionInfo, ex);
			return;
		}
		
		//Create a log entry for the cron process
		CronLogEntry cronEntry = new CronLogEntry();
		if (!cronEntry.saveToDatabase(vufindConn, logger)){
			logger.error("Could not save log entry to database, quitting");
			return;
		}
		
		// Read the cron INI file to get information about the processes to run
		Ini cronIni = new Ini();
		File cronConfigFile = new File("../../sites/" + serverName + "/conf/config.cron.ini");
		try {
			cronIni.load(new FileReader(cronConfigFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Cron Configuration file is not valid.  Please check the syntax of the file.");
		} catch (FileNotFoundException e) {
			logger.error("Cron Configuration file (" + cronConfigFile.getPath() + ") could not be found.  You must supply a configuration file in conf called config.ini.");
		} catch (IOException e) {
			logger.error("Cron Configuration file could not be read.");
		}
		
		
		//Check to see if a specific task has been specified to be run
		ArrayList<ProcessToRun> processesToRun = new ArrayList<ProcessToRun>();
		// INI file has a main section for processes to be run
		// The processes are in the format:
		// name = handler class
		boolean updateConfig = false;
		Section processes = cronIni.get("Processes");
		if (args.length >= 1){
			logger.info("Found " + args.length + " arguments ");
			String processName = args[0];
			String processHandler = cronIni.get("Processes", processName);
			if (processHandler == null){
				processHandler = processName;
			}
			ProcessToRun process = new ProcessToRun(processName, processHandler);
			args = Arrays.copyOfRange(args, 1, args.length);
			if (args.length > 0){
				process.setArguments(args);
			}
			processesToRun.add(process);
		}else{
			//Load processes to run
			processesToRun = loadProcessesToRun(cronIni, processes);
			updateConfig = true;
		}
		
		for (ProcessToRun processToRun: processesToRun){
			Section processSettings;
			if (processToRun.getArguments() != null){
				//Add arguments into the section
				for (String argument : processToRun.getArguments() ){
					String[] argumentOptions = argument.split("=");
					logger.info("Adding section setting " + argumentOptions[0] + " = " + argumentOptions[1]);
					cronIni.put("runtimeArguments", argumentOptions[0], argumentOptions[1]);
				}
				processSettings = cronIni.get("runtimeArguments");
			}else{
				processSettings = cronIni.get(processToRun.getProcessName());
			}
		
			currentTime = new Date();
			logger.info(currentTime.toString() + ": Running Process " + processToRun.getProcessName());
			if (processToRun.getProcessClass() == null){
				logger.error("Could not run process " + processToRun.getProcessName() + " because there is not a class for the process.");
				cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because there is not a class for the process.");
				continue;
			}
			// Load the class for the process using reflection
			try {
				@SuppressWarnings("rawtypes")
				Class processHandlerClass = Class.forName(processToRun.getProcessClass());
				Object processHandlerClassObject;
				try {
					processHandlerClassObject = processHandlerClass.newInstance();
					IProcessHandler processHandlerInstance = (IProcessHandler) processHandlerClassObject;
					cronEntry.addNote("Starting cron process " + processToRun.getProcessName());
					
					if (updateConfig){
						//Mark the time the run was started rather than finished so really long running processes 
						//can go on while faster processes execute multiple times in other threads. 
						cronIni.put(processToRun.getProcessName(), "lastRun", currentTime.getTime());
						cronIni.put(processToRun.getProcessName(), "lastRunFormatted", currentTime.toString());
						try {
							cronIni.store(cronConfigFile);
						} catch (IOException e) {
							// TODO Auto-generated catch block
							logger.error("Unable to update configuration file.");
						}
					}
					processHandlerInstance.doCronProcess(serverName, ini, processSettings, vufindConn, econtentConn, cronEntry, logger);
					//Log how long the process took
					Date endTime = new Date();
					long elapsedMillis = endTime.getTime() - currentTime.getTime();
					float elapsedMinutes = (elapsedMillis) / 60000;
					logger.info("Finished process " + processToRun.getProcessName() + " in " + elapsedMinutes + " minutes (" + elapsedMillis + " milliseconds)");
					cronEntry.addNote("Finished process " + processToRun.getProcessName() + " in " + elapsedMinutes + " minutes (" + elapsedMillis + " milliseconds)");
					// Update that the process was run.
					currentTime = new Date();
					
				} catch (InstantiationException e) {
					logger.error("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be instantiated.");
					cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be instantiated.");
				} catch (IllegalAccessException e) {
					logger.error("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " generated an Illegal Access Exception.");
					cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " generated an Illegal Access Exception.");
				}

			} catch (ClassNotFoundException e) {
				logger.error("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be found.");
				cronEntry.addNote("Could not run process " + processToRun.getProcessName() + " because the handler class " + processToRun.getProcessClass() + " could not be be found.");
			}
		}
			
		if (updateConfig){
			try {
				cronIni.store(cronConfigFile);
			} catch (IOException e) {
				// TODO Auto-generated catch block
				logger.error("Unable to update configuration file.");
			}
		}
		
		cronEntry.setFinished();
		cronEntry.addNote("Cron run finished");
		cronEntry.saveToDatabase(vufindConn, logger);
	}

	private static ArrayList<ProcessToRun> loadProcessesToRun(Ini cronIni, Section processes) {
		ArrayList<ProcessToRun> processesToRun = new ArrayList<ProcessToRun>();
		Date currentTime = new Date();
		for (String processName : processes.keySet()) {
			String processHandler = cronIni.get("Processes", processName);
			// Each process has its own configuration section which can include:
			// - time last run
			// - interval to run the process
			// - additional configuration information for the process
			// Check to see when the process was last run
			String lastRun = cronIni.get(processName, "lastRun");
			boolean runProcess = false;
			String frequencyHours = cronIni.get(processName, "frequencyHours");
			if (frequencyHours == null || frequencyHours.length() == 0){
				//If the frequency isn't set, automatically run the process 
				runProcess = true;
			}else if (frequencyHours.trim().compareTo("-1") == 0) {
				// Process has to be run manually
				runProcess = false;
				logger.info("Skipping Process " + processName + " because it must be run manually.");
			}else{
				//Frequency is a number of hours.  See if we should run based on the last run. 
				if (lastRun == null || lastRun.length() == 0) {
					runProcess = true;
				} else {
					// Check the interval to see if the process should be run
					try {
						long lastRunTime = Long.parseLong(lastRun);
						if (frequencyHours.trim().compareTo("0") == 0) {
							// There should not be a delay between cron runs
							runProcess = true;
						} else {
							int frequencyHoursInt = Integer.parseInt(frequencyHours);
							if ((double) (currentTime.getTime() - lastRunTime) / (double) (1000 * 60 * 60) >= frequencyHoursInt) {
								// The elapsed time is greater than the frequency to run
								runProcess = true;
							}else{
								logger.info("Skipping Process " + processName + " because it has already run in the specified interval.");
							}
	
						}
					} catch (NumberFormatException e) {
						logger.warn("Warning: the lastRun setting for " + processName + " was invalid. " + e.toString());
					}
				}
			}
			if (runProcess) {
				logger.info("Running process " + processName);
				processesToRun.add(new ProcessToRun(processName, processHandler));
			}
		}
		return processesToRun;
	}

}
