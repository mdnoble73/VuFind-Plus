<div data-role="page" id="Search-reserves">
  {include file="header.tpl"}
  <div data-role="content">
    <h3>{translate text='Search For Items on Reserve'}</h3>
    <form method="get" action="{$path}/Search/Reserves" data-ajax="false">
      <div data-role="fieldcontain">
        <label for="reserves_by_course">{translate text='By Course'}:</label>
        <select name="course" id="reserves_by_course">
          <option value=""></option>
          {foreach from=$courseList item=courseName key=courseId}
            <option value="{$courseId|escape}">{$courseName|escape}</option>
          {/foreach}
        </select>
      </div>
      <div data-role="fieldcontain">
        <input type="submit" name="submit" value="{translate text='Find'}"/>
      </div>
    </form>
          
    <form method="get" action="{$path}/Search/Reserves" data-ajax="false">
      <div data-role="fieldcontain">  
        <label for="reserves_by_inst">{translate text='By Instructor'}:</label>
        <select name="inst" id="reserves_by_inst">
          <option value=""></option>
          {foreach from=$instList item=instName key=instId}
            <option value="{$instId|escape}">{$instName|escape}</option>
          {/foreach}
        </select>
      </div>
      <div data-role="fieldcontain">
        <input type="submit" name="submit" value="{translate text='Find}"/>
      </div>
    </form>
          
    <form method="get" action="{$path}/Search/Reserves" data-ajax="false">
      <div data-role="fieldcontain"> 
        <label for="reserves_by_dept">{translate text='By Department'}:</label>
        <select name="dept" id="reserves_by_dept">
          <option value=""></option>
          {foreach from=$deptList item=deptName key=deptId}
            <option value="{$deptId|escape}">{$deptName|escape}</option>
          {/foreach}
        </select>
      </div>
      <div data-role="fieldcontain">
        <input type="submit" name="submit" value="{translate text='Find'}"/>
      </div>
    </form>
  </div>
  {include file="footer.tpl"}
</div>
