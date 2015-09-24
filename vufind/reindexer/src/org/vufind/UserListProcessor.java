package org.vufind;

import org.apache.log4j.Logger;
import org.apache.solr.client.solrj.SolrQuery;
import org.apache.solr.client.solrj.SolrServer;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrServer;
import org.apache.solr.client.solrj.response.QueryResponse;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrDocumentList;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

/**
 * Handles setting up solr documents for User Lists
 *
 * Pika
 * User: Mark Noble
 * Date: 7/10/2015
 * Time: 5:14 PM
 */
public class UserListProcessor {
	private GroupedWorkIndexer indexer;
	private Connection vufindConn;
	private Logger logger;
	private boolean fullReindex;
	private int availableAtLocationBoostValue;
	private int ownedByLocationBoostValue;

	public UserListProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Logger logger, boolean fullReindex, int availableAtLocationBoostValue, int ownedByLocationBoostValue){
		this.indexer = indexer;
		this.vufindConn = vufindConn;
		this.logger = logger;
		this.fullReindex = fullReindex;
		this.availableAtLocationBoostValue = availableAtLocationBoostValue;
		this.ownedByLocationBoostValue = ownedByLocationBoostValue;

	}

	public Long processPublicUserLists(long lastReindexTime, ConcurrentUpdateSolrServer updateServer, SolrServer solrServer) {
		GroupedReindexMain.addNoteToReindexLog("Starting to process public lists");
		Long numListsProcessed = 0l;
		try{
			PreparedStatement listsStmt;
			if (fullReindex){
				//Delete all lists from the index
				updateServer.deleteByQuery("recordtype:list");
				//Get a list of all public lists
				listsStmt = vufindConn.prepareStatement("SELECT user_list.id as id, deleted, public, title, description, user_list.created, dateUpdated, firstname, lastname, displayName, homeLocationId from user_list INNER JOIN user on user_id = user.id WHERE public = 1 AND deleted = 0");
			}else{
				//Get a list of all lists that are were changed since the last update
				listsStmt = vufindConn.prepareStatement("SELECT user_list.id as id, deleted, public, title, description, user_list.created, dateUpdated, firstname, lastname, displayName, homeLocationId from user_list INNER JOIN user on user_id = user.id WHERE dateUpdated > ?");
				listsStmt.setLong(1, lastReindexTime);
			}

			PreparedStatement getTitlesForListStmt = vufindConn.prepareStatement("SELECT groupedWorkPermanentId, notes from user_list_entry WHERE listId = ?");
			ResultSet allPublicListsRS = listsStmt.executeQuery();
			while (allPublicListsRS.next()){
				updateSolrForList(updateServer, solrServer, getTitlesForListStmt, allPublicListsRS);
				numListsProcessed++;
			}
			updateServer.commit();

		}catch (Exception e){
			logger.error("Error processing public lists", e);
		}
		logger.info("Finished processing public lists");
		GroupedReindexMain.addNoteToReindexLog("Finished processing public lists");
		return numListsProcessed;
	}

	private void updateSolrForList(ConcurrentUpdateSolrServer updateServer, SolrServer solrServer, PreparedStatement getTitlesForListStmt, ResultSet allPublicListsRS) throws SQLException, SolrServerException, IOException {
		UserListSolr userListSolr = new UserListSolr(indexer);
		Long listId = allPublicListsRS.getLong("id");

		int deleted = allPublicListsRS.getInt("deleted");
		int isPublic = allPublicListsRS.getInt("public");
		if (deleted == 1 || isPublic == 0){
			updateServer.deleteByQuery("id:list");
		}else{
			userListSolr.setId(listId);
			userListSolr.setTitle(allPublicListsRS.getString("title"));
			userListSolr.setDescription(allPublicListsRS.getString("description"));
			userListSolr.setCreated(allPublicListsRS.getLong("created"));

			String displayName = allPublicListsRS.getString("displayName");
			String firstName = allPublicListsRS.getString("firstname");
			String lastName = allPublicListsRS.getString("lastname");
			if (displayName != null && displayName.length() > 0){
				userListSolr.setAuthor(displayName);
			}else{
				if (firstName == null) firstName = "";
				if (lastName == null) lastName = "";
				String firstNameFirstChar = "";
				if (firstName.length() > 0){
					firstNameFirstChar = firstName.charAt(0) + ". ";
				}
				userListSolr.setAuthor(firstNameFirstChar + lastName);
			}

			//Get information about all of the list titles.
			getTitlesForListStmt.setLong(1, listId);
			ResultSet allTitlesRS = getTitlesForListStmt.executeQuery();
			while (allTitlesRS.next()){
				String groupedWorkId = allTitlesRS.getString("groupedWorkPermanentId");
				SolrQuery query = new SolrQuery();
				query.setQuery("id:" + groupedWorkId + " AND recordtype:grouped_work");
				query.setFields("title", "author");

				QueryResponse response = solrServer.query(query);
				SolrDocumentList results = response.getResults();
				//Should only ever get one response
				if (results.size() >= 1){
					SolrDocument curWork = results.get(0);
					userListSolr.addListTitle(groupedWorkId, curWork.getFieldValue("title"), curWork.getFieldValue("author"));
				}
			}

			updateServer.add(userListSolr.getSolrDocument(availableAtLocationBoostValue, ownedByLocationBoostValue));
		}
	}
}
