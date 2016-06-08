

INSERT INTO `materials_request_status` (`id`, `description`, `isDefault`, `sendEmailToPatron`, `emailTemplate`, `isOpen`, `isPatronCancel`, `libraryId`) VALUES
(1, 'Request Pending', 1, 0, '', 1, 0, -1),
(2, 'Already owned/On order', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.', 0, 0, -1),
(3, 'Item purchased', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0, 0, -1),
(4, 'Referred to Collection Development - Adult', 0, 0, '', 1, 0, -1),
(5, 'Referred to Collection Development - J/YA', 0, 0, '', 1, 0, -1),
(6, 'Referred to Collection Development - AV', 0, 0, '', 1, 0, -1),
(7, 'ILL Under Review', 0, 0, '', 1, 0, -1),
(8, 'Request Referred to ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library''s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1, 0, -1),
(9, 'Request Filled by ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0, 0, -1),
(10, 'Ineligible ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0, 0, -1),
(11, 'Not enough info - please contact Collection Development to clarify', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1, 0, -1),
(12, 'Unable to acquire the item - out of print', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0, 0, -1),
(13, 'Unable to acquire the item - not available in the US', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0, 0, -1),
(14, 'Unable to acquire the item - not available from vendor', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0, 0, -1),
(15, 'Unable to acquire the item - not published', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0, 0, -1),
(16, 'Unable to acquire the item - price', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1),
(17, 'Unable to acquire the item - publication date', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1),
(18, 'Unavailable', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0, 0, -1),
(19, 'Cancelled by Patron', 0, 0, '', 0, 1, -1),
(20, 'Cancelled - Duplicate Request', 0, 0, '', 0, 0, -1);
