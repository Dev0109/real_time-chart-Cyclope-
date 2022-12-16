<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class reports
{
	var $dbu;
	
	// function reports()
	// {
	// 	$this->dbu = new mysql_db();
	// }
	
	/****************************************************************
	* function overview(&$ld)                                       *
	****************************************************************/
	function overview(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->overview_validate($ld))
		{
			return false;
		}
		global $glob;
		include(CURRENT_VERSION_FOLDER.'php/ajax/xstats.php');
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'overview.csv'));
		$ft->define_dynamic('day_summary_row','main');
		$ft->define_dynamic('topapplications_row','main');
		$ft->define_dynamic('topwebsites_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		//	productivty

						$chart = array();
						$apps = array();
						$session_website = get_session_website_table();
						$nodes = explode('-',$glob['f']);
						$department_id = reset($nodes);
						$prod_color = array('red' => 'EB544D', 'rest'=>end($_SESSION['colors']),'green' => '5EE357');
						$prod = array('red', 'rest', 'green');
						$labels = array('red' => $ft->lookup('Distracting'), 'rest' => $ft->lookup('Neutral'), 'green' => $ft->lookup('Productive'));

						$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
						extract($filters,EXTR_OVERWRITE);
						$chart = array('red'=>0,'rest'=>0,'green'=>0);

						$session = $dbu->row("SELECT SUM(session_application.duration) AS duration FROM session_application
												INNER JOIN session ON session.session_id = session_application.session_id 
												".$app_join."
												WHERE session_application.time_type = 0 AND 1=1 ".$app_filter);
						$chart['total'] = $session['duration'];

						$productivity_filter = ' = ' . filter_var($department_id, FILTER_SANITIZE_NUMBER_INT);
						$count = 1;
						$department_list = get_department_children($department_id,1);
						$querystring = "SELECT     Sum(session_application.duration) AS app_duration, 
													 application.description           AS name, 
													 application.application_type      AS type, 
													 application.application_id, 
													 application_productivity.application_productivity_id AS application_productivity_id,
													 COALESCE(application_productivity.productive,1) AS productive
										  FROM       session_application 
										  INNER JOIN application 
										  ON         application.application_id = session_application.application_id 
											AND      application.application_type != 3 
										  INNER JOIN session 
										  ON         session.session_id = session_application.session_id 
										  ".$app_join."
										  LEFT JOIN  application_productivity 
										  ON         application_productivity.department_id ".$productivity_filter." 
										  AND        application_productivity.link_id = application.application_id 
										  AND        application_productivity.link_type < 3 
										  AND        member.department_id IN (" . $department_list . ")
										  WHERE      session_application.time_type = 0 
										  AND        2=2 
										  ".$app_filter." 
										  GROUP BY   session_application.application_id 
						union 
								 SELECT     sum(session_website_agg.duration)               AS app_duration,
													   domain.domain                                   AS name, 
													   '3'                                             AS type, 
													   domain.domain_id                                AS application_id,
													   application_productivity.application_productivity_id AS application_productivity_id, 
													   COALESCE(application_productivity.productive,1) AS productive
											FROM       session_website_agg 
											INNER JOIN domain 
											ON         domain.domain_id = session_website_agg.domain_id 
											INNER JOIN session 
											ON         session.session_id = session_website_agg.session_id 
											".$app_join."
											LEFT JOIN  application_productivity 
											ON         application_productivity.department_id ".$productivity_filter." 
											AND        application_productivity.link_id = domain.domain_id 
											AND        application_productivity.link_type = 3 
											AND        member.department_id IN (" . $department_list . ")
											WHERE      session_website_agg.time_type = 0 
											AND        2=2 
											".$app_filter."
											GROUP BY   session_website_agg.domain_id";

						$productivity = $dbu->query($querystring);
						while ($productivity->next()){
							switch ($productivity->f('productive')){
								case 0:
									$chart['red'] += $productivity['app_duration'];
									break;			
								case 2:
									$chart['green'] += $productivity['app_duration'];
									break;
							}
						}
						$chart['rest'] = $chart['total'] - (($chart['red'] + $chart['green']));
						$total = $chart['total'] > 0 ? $chart['total'] : 1;
						$chart_prod = $chart['green'];
						$chart_dist = $chart['red'];
						$chart_neut = $chart['rest'];
						unset($chart);
						$chart['green'] = $chart_prod;
						$chart['red'] = $chart_dist;
						$chart['rest'] = $chart_neut;

								$chart['rest'] = $total - ($chart['red'] + $chart['green']);
								if ($total != 0)
								{
									$ft->assign(array(
										'PRODUCTIVE_TOTAL' => number_format($chart['green'] / $total *100,2,'.',','),
										'DISTRACTING_TOTAL' => number_format($chart['red'] / $total * 100, 2,'.',','),
										'NEUTRAL_TOTAL' => number_format($chart['rest'] / $total * 100, 2 ,'.',',')
									));
								}
		
		//	monitorred
		
		$monitored = $this->dbu->row("SELECT count(member.member_id) AS members_number
											FROM member 
											INNER JOIN computer2member ON computer2member.member_id = member.member_id
											INNER JOIN computer ON computer.computer_id = computer2member.computer_id
											WHERE member.active != 3 AND member.department_id != 0
												AND computer2member.last_record > (" . time() . " - (computer.connectivity * 60 * 2)) ");
		$monitored_all = $this->dbu->row("SELECT count(member.member_id) AS members_number
											FROM member 
											INNER JOIN computer2member ON computer2member.member_id = member.member_id
											INNER JOIN computer ON computer.computer_id = computer2member.computer_id
											WHERE member.active != 3 AND member.department_id != 0");
											
		
			$ft->assign(array(
				'MONITOR_COUNT' => $monitored['members_number'],
				'MONITOR_ALL_COUNT' => $monitored_all['members_number'],
			));
		
		//hours summary
		$matches = array(); 
		preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$_SESSION['filters']['time']['time'],$matches);
		$matches = array_shift($matches);
		$start = strtotime($matches[0]);
		$end = strtotime($matches[1]);

		if (!$end)
		{
			$days= 1;
		}
		else 
		{
			$days = ( $end - $start ) / 86400;
			$days++;
		}
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);

        //ALEX - START ANCOM level idle time with Attendance idle time
        
        if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){
        	$onlycompfilter = '';
        }else {
        	$onlycompfilter = '
        INNER JOIN computer2member ON computer2member.member_id = member.member_id
        INNER JOIN computer ON computer.computer_id = session.computer_id';
        }
        
        $_SESSION['first_last_only'] = 1;
        $secondary_condition = "AND session_activity.activity_type = 1 AND session_attendance.active = 1";
        
        $sortable_columns = array(
        	'session_attendance.start_time',
        	'member.logon',
        	'session_attendance.start_time',
        	);
        $sortcolumns = get_sorting($sortable_columns,'','desc');
        
        $dbu->query("SELECT MIN(session_attendance.start_time) AS start_work,
        					MAX(session_attendance.end_time) AS end_work
        			FROM session_activity
        			INNER JOIN session ON session.session_id = session_activity.session_id
        			".$app_join."
        			INNER JOIN session_attendance ON session_attendance.session_id = session_activity.session_id
        			".$onlycompfilter."
        			WHERE 1=1
        			".$secondary_condition."
        			AND session_attendance.start_time >= session.date
        			".$app_filter."
        			GROUP BY member.member_id,session_activity.session_id
        			" . $sortcolumns . " ");
        
        $ttl_total = 0;
        while ($dbu->move_next()){
        	$ttl_total = $ttl_total + ($dbu->f('end_work') - $dbu->f('start_work'));
        	/*print "<pre>";
        	print_r("<br/>");
        	print "</pre>";*/
        }
        
        $total_time = $ttl_total;
        $new_idle = $total_time - $glob['stats_active'];
        $glob['stats_idle'] = $new_idle;
        
        //ALEX - END ANCOM level idle time with Attendance idle time		
		
		$ft->assign(array(
			'TITLE' => 'Overview',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
			'G_TOTAL' => format_time($glob['stats_active'] + $glob['stats_idle'],false),
			'G_ACTIVE' => format_time($glob['stats_active'],false),
			'G_IDLE' => format_time($glob['stats_idle'],false),
			'G_ACTIVE_PROC' => $total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0,
			'G_IDLE_PROC' => $total_time ? number_format($glob['stats_idle'] * 100 / $total_time,2) : 0,
			'G_ONLINE_PROC' => $total_time ? number_format($glob['stats_online'] * 100 / $total_time,2) : 0,
		));
		
		$active = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
										session_activity.hour,
										session_activity.day 
										FROM session_activity
										INNER JOIN session ON session.session_id = session_activity.session_id
										".$app_join."
										WHERE session_activity.activity_type = 1
										".$app_filter."
										GROUP BY session_activity.hour");
		while ($active->next())
		{
			if(!is_array($data[$active->f('hour')])){
				$data[$active->f('hour')] = array('active'=>0,'active_format' => 0,'idle'=>0,'idle_format' =>0);
			}
			$data[$active->f('hour')]['active'] = ($active->f('duration') * 100) / (3600 * $days * $members );
			$data[$active->f('hour')]['active_format'] = format_time($active->f('duration'));
		}
		
		$idle = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
							session_activity.hour,
							session_activity.day 
							FROM session_activity
							INNER JOIN session ON session.session_id = session_activity.session_id
							".$app_join."
							WHERE session_activity.activity_type = 0
							".$app_filter."
							GROUP BY session_activity.hour");
		
		while ($idle->next()){
			if(!is_array($data[$idle->f('hour')])){
				$data[$idle->f('hour')] = array('active'=>0,'active_format' => 0,'idle'=>0,'idle_format' =>0);
			}
			
			$data[$idle->f('hour')]['idle'] = ($idle->f('duration') * 100)/ (3600 * $days * $members);
			$data[$idle->f('hour')]['idle_format'] = format_time($idle->f('duration'));
		}
		
		$private = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
							session_activity.hour,
							session_activity.day 
							FROM session_activity
							INNER JOIN session ON session.session_id = session_activity.session_id
							".$app_join."
							WHERE session_activity.activity_type > 1
							".$app_filter."
							GROUP BY session_activity.hour");
		
		while ($private->next()){
			if(!is_array($data[$private->f('hour')])){
				$data[$idle->f('hour')] = array('private'=>0,'private_format' => 0,
												'active'=>0,'active_format' => 0,
												'idle'=>0,'idle_format' =>0);
			}
			
			$data[$private->f('hour')]['private'] = ($private->f('duration') * 100)/ (3600 * $days * $members);
			$data[$private->f('hour')]['private_format'] = format_time($private->f('duration'));
		}

		if(empty($data)){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		
		for($i = 0; $i < 24; $i++){
			
			if($data[$i]['active'] || $data[$i]['idle'] || $data[$i]['private'])
			{
				$start_hour = ($i > 12) ? $i-12 : $i;
				$start_ampm = ($i < 12) ? 'AM' : 'PM';
				
				$end_hour = ($i+1 > 12) ? $i+1-12 : $i+1;
				$end_ampm = ($i+1 < 12) ? 'AM' : 'PM';
				
				if($end_hour == 12 && $end_ampm == 'PM')
				{
					$end_hour = 0;
					$end_ampm = 'AM';
					
				}
				
				$ft->assign('HOUR', $start_hour.$start_ampm.' - '.$end_hour.$end_ampm);
				$ft->assign('ACTIVE',number_format($data[$i]['active'],2));	
				$ft->assign('IDLE',number_format($data[$i]['idle'],2));
				$ft->assign('PRIVATE',number_format($data[$i]['private'],2));
				$ft->parse('DAY_SUMMARY_ROW_OUT','.day_summary_row');
			}
			
		}
		
		/****************************************************************
		* function topapplications(&$ld)                                *
		****************************************************************/
		$categories = get_categories($_SESSION['filters']['f'],'all');

		$session = $this->dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."  WHERE 1 = 1 AND session_application.time_type = 0 ". $app_filter);
		
		$total = $session['duration'];
		
		$application = $this->dbu->query("SELECT SUM(session_application.duration) as app_duration,
			application.description as name, 
			session_application.application_id 
			 FROM session_application 
			INNER JOIN application ON application.application_id = session_application.application_id
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."
			WHERE session_application.duration > 0
			AND session_application.time_type = 0 
			".$app_filter."
			GROUP BY session_application.application_id
			ORDER BY app_duration desc
			LIMIT 0,5");
		
		$i = 0;
		$tot = 0;
		
		while ($application->next()){
			$proc = ($application->f('app_duration') * 100 / $total);
			
			$this->dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name,member.logon,
				member.alias,
				CONCAT(member.first_name,' ',member.last_name) AS member_name
				FROM session_application 
				INNER JOIN application ON application.application_id = session_application.application_id
				INNER JOIN session ON session.session_id = session_application.session_id
				".$app_join."
				WHERE session_application.duration > 0 AND session_application.application_id = '".$application->f('application_id')."'
				AND session_application.time_type = 0 ".$app_filter."
				GROUP BY member.member_id
				ORDER BY app_duration desc");
			
			$user = '';
			while ($this->dbu->move_next()) {
				$logon = $this->dbu->f('alias') == 1 ?  $this->dbu->f('member_name') :  $this->dbu->f('logon');
				$user .= $logon." - ".format_time($this->dbu->f('app_duration'))."\n";
			}
			
			$cat_name = $ft->lookup('Uncategorised');
	
			if(isset($categories[$application->f('application_id').'-0'])){
				$cat_name = $ft->lookup($categories[$application->f('application_id').'-0']['category']);
			}
			else if(isset($categories[$application->f('application_id').'-1'])){
					$cat_name = $ft->lookup($categories[$application->f('application_id').'-1']['category']);
				}
				else if(isset($categories[$application->f('application_id').'-2'])){
						$cat_name = $ft->lookup($categories[$application->f('application_id').'-2']['category']);
					}
					else if(isset($categories[$application->f('application_id').'-3'])){
							$cat_name = $ft->lookup($categories[$application->f('application_id').'-3']['category']);
						}
			
			$user = rtrim($user);
			$ft->assign(array(
				'APPLICATION' => decode_numericentity($application->f('name')),
				'CATEGORY' => decode_numericentity($cat_name), 
				'USERNAME' => decode_numericentity($user),
				'PERCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($application->f('app_duration')) / 3600),
				'TOTAL_TIME_M' => (intval($application->f('app_duration')) / 60) % 60,
				'TOTAL_TIME_S' => intval($application->f('app_duration')) % 60,	
			));
			
			
			$ft->parse('TOPAPPLICATIONS_ROW_OUT','.topapplications_row');
			$i++;
		}
		
		//	top websites
		
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$session = $this->dbu->row("SELECT SUM(session_website.duration) AS duration FROM session_website 
			INNER JOIN session ON session.session_id = session_website.session_id
			INNER JOIN domain ON session_website.domain_id = domain.domain_id
			".$app_join." WHERE 1=1
			AND session_website.time_type = 0
			". $app_filter);
		
		$total = $session['duration'];
		
		$website = $this->dbu->query("SELECT SUM(session_website.duration) as website_duration,
			domain.domain,
			session_website.domain_id
			FROM session_website
			INNER JOIN domain ON domain.domain_id = session_website.domain_id
			INNER JOIN session ON session.session_id = session_website.session_id
			".$app_join."
			WHERE 1=1 AND session_website.time_type = 0 ".$app_filter."
			AND session_website.duration > 0
			GROUP BY session_website.domain_id
			ORDER BY website_duration desc
			LIMIT 0,5");
		
		$i = 0;
		$tot = 0;
		while ($website->next()){
			$proc = ($website->f('website_duration') * 100 / $total);
				
			$this->dbu->query("SELECT SUM(session_website.duration) as website_duration, member.logon,
				member.alias,
				CONCAT(member.first_name,' ',member.last_name) AS member_name FROM session_website 
				INNER JOIN session ON session.session_id = session_website.session_id
				INNER JOIN domain ON session_website.domain_id = domain.domain_id
				".$app_join."
				WHERE session_website.duration > 0 AND session_website.time_type = 0 AND session_website.domain_id = '".$website->f('domain_id')."'
				".$app_filter."
				GROUP BY member.member_id
				ORDER BY website_duration desc");
			
			$user = "";
			while ($this->dbu->move_next()) {
				$logon = $this->dbu->f('alias') == 1 ? $this->dbu->f('member_name'): $this->dbu->f('logon');
				$user .= $logon." - ".format_time($this->dbu->f('website_duration'))."\n";
			}
			
			$user = rtrim($user);
			
			$ft->assign(array(
				'W_WWW' => $website->f('domain'),
				'W_USERNAME' => decode_numericentity($user),
				'W_PERCENT' => number_format($proc,2,',','.'),
				'W_TOTAL_TIME_H' => intval(intval($website->f('website_duration')) / 3600),
				'W_TOTAL_TIME_M' => (intval($website->f('website_duration')) / 60) % 60,
				'W_TOTAL_TIME_S' => intval($website->f('website_duration')) % 60,	
			));
			$tot += $website->f('website_duration');
			$ft->parse('TOPWEBSITES_ROW_OUT','.topwebsites_row');
			$i++;
		}
		
		
		$output_file = $ft->lookup('Overview').'.csv';
		$ft->parse('CONTENT','main');
		
		// echo '<pre>';print_r($ft);echo '</pre>';exit;
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents);
			if($_SESSION['attachment_name'])
				{
						$tmp = ini_get('upload_tmp_dir');
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);
				}
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	// var_dump("test1");

	/****************************************************************
	* function overview_validate(&$ld)                              *
	****************************************************************/
	function overview_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function overtime(&$ld)                                       *
	****************************************************************/
	function overtime(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->overtime_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'overtime.csv'));
		$ft->define_dynamic('day_summary_row','main');
		$ft->define_dynamic('week_summary_row','main');
		$ft->define_dynamic('application_summary_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		
		//hours summary
		$matches = array(); 
		preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$_SESSION['filters']['time']['time'],$matches);
		$matches = array_shift($matches);
		$start = strtotime($matches[0]);
		$end = strtotime($matches[1]);

		if (!$end) {
			$days= 1;
		}
		else 
		{
			$days = ( $end - $start ) / 86400;
			$days++;
		}

		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$pieces = split('-',$_SESSION['filters']['f']);
		$pieces[0] = substr($pieces[0],1);
		
		$workschedule = get_workschedule($pieces[0]);
		
		$active = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
								session_activity.hour,
								session_activity.day 
								FROM session_activity
								INNER JOIN session ON session.session_id = session_activity.session_id
								".$app_join."
								WHERE session_activity.activity_type = 1
								".$app_filter."
								GROUP BY session_activity.day, session_activity.hour");
		
		while ($active->next()){
			if(!is_array($data[$active->f('hour')])){
				$data[$active->f('hour')] = array('active'=>0,'active_format' => 0,'private'=>0,'private_format' => 0,'idle'=>0,'idle_format' =>0,'overtime'=> 0,'overtime_format' => 0);
			}
			
			if( ($active->f('hour') < $workschedule[$active->f('day')]['start_hour'] ) ||( $active->f('hour') >= $workschedule[$active->f('day')]['end_hour'] ) )
			{
				$data[$active->f('hour')]['overtime'] += ($active->f('duration') * 100) / (3600 * $days * $members );
				$data[$active->f('hour')]['overtime_format'] = format_time($data[$active->f('hour')]['overtime']);
			}
			else 
			{
				$data[$active->f('hour')]['active'] += ($active->f('duration') * 100) / (3600 * $days * $members );
				$data[$active->f('hour')]['active_format'] = format_time($data[$active->f('hour')]['active']);
			}
		}
		
		$idle = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
							session_activity.hour,
							session_activity.day 
							FROM session_activity
							INNER JOIN session ON session.session_id = session_activity.session_id
							".$app_join."
							WHERE session_activity.activity_type = 0
							".$app_filter."
							GROUP BY session_activity.day, session_activity.hour");
		
		while ($idle->next()){
			if(!is_array($data[$idle->f('hour')])){
				$data[$idle->f('hour')] = array('active'=>0,'active_format' => 0,'private'=>0,'private_format' => 0,'idle'=>0,'idle_format' =>0);
			}
			
			$data[$idle->f('hour')]['idle'] += ($idle->f('duration') * 100) / (3600 * $days * $members );
			$data[$idle->f('hour')]['idle_format'] = format_time($data[$idle->f('hour')]['idle']);
		
		}

		$private = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
									session_activity.hour,
									session_activity.day 
									FROM session_activity
									INNER JOIN session ON session.session_id = session_activity.session_id
									".$app_join."
									WHERE session_activity.activity_type > 1
									".$app_filter."
									GROUP BY session_activity.hour");
		
		while ($private->next()){
			if(!is_array($data[$private->f('hour')])){
				$data[$private->f('hour')] = array('active'=>0,'active_format' => 0,'private'=>0,'private_format' => 0,'idle'=>0,'idle_format' =>0);
			}
			
			$data[$private->f('hour')]['private'] = ($private->f('duration') * 100)/ (3600 * $days * $members);
			$data[$private->f('hour')]['private_format'] = format_time($private->f('duration'));
		}
		
		if(empty($data)){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		
		$ft->assign(array(
			'TITLE' => 'Overtime',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		for($i = 0; $i < 24; $i++){
			
			if($data[$i]['active'] || $data[$i]['idle'] || $data[$i]['private'] || $data[$i]['overtime'])
			{
				$start_hour = ($i > 12) ? $i-12 : $i;
				$start_ampm = ($i < 12) ? 'AM' : 'PM';
				
				$end_hour = ($i+1 > 12) ? $i+1-12 : $i+1;
				$end_ampm = ($i+1 < 12) ? 'AM' : 'PM';
				
				if($end_hour == 12 && $end_ampm == 'PM')
				{
					$end_hour = 0;
					$end_ampm = 'AM';
					
				}
				
				$ft->assign('HOUR', $start_hour.$start_ampm.' - '.$end_hour.$end_ampm);
				$ft->assign('ACTIVE',number_format($data[$i]['active'],2));	
				$ft->assign('IDLE',number_format($data[$i]['idle'],2));
				$ft->assign('OVERTIME',number_format($data[$i]['overtime'],2));
				$ft->assign('PRIVATE',number_format($data[$i]['private'],2));
				$ft->parse('DAY_SUMMARY_ROW_OUT','.day_summary_row');
			}
			
		}
		
		// weekday summary 
		
		$active = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
									session_activity.day,
									session_activity.hour 
								 	FROM session_activity
								 	INNER JOIN session ON session.session_id = session_activity.session_id
								 	".$app_join."
								WHERE session_activity.activity_type = 1 
								".$app_filter."
								GROUP BY session_activity.hour, session_activity.day");
		
		while ($active->next()){
			if(!is_array($data[$active->f('day')])){
				$data[$active->f('day')] = array('active'=>0,'idle'=>0,'days' => 0,'overtime'=>0,'private'=>0);
			}
			
			if( ($active->f('hour') < $workschedule[$active->f('day')]['start_hour'] ) ||( $active->f('hour') >= $workschedule[$active->f('day')]['end_hour'] ) )
			{
				$data[$active->f('day')]['overtime'] += $active->f('duration') / 3600;
			}
			else 
			{
				$data[$active->f('day')]['active'] += $active->f('duration') / 3600;
			}
			
			
		}
		$idle = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
									session_activity.day,
									session_activity.hour FROM session_activity
									INNER JOIN session ON session.session_id = session_activity.session_id
									".$app_join."
									WHERE session_activity.activity_type = 0 
									".$app_filter."
									GROUP BY session_activity.hour, session_activity.day");
		
		while ($idle->next()){
			if(!is_array($data[$idle->f('day')])){
				$data[$idle->f('day')] = array('active'=>0,'idle'=>0,'days' => 0,'overtime'=>0,'private'=>0);
			}
			$data[$idle->f('day')]['idle'] += $idle->f('duration') / 3600;
		}
		
		$private = $this->dbu->query("SELECT SUM(session_activity.duration) AS duration,
									session_activity.day FROM session_activity
									INNER JOIN session ON session.session_id = session_activity.session_id
									".$app_join."
								WHERE session_activity.activity_type > 1 
								".$app_filter."
								GROUP BY day");
		
		while ($private->next()){
			if(!is_array($data[$private->f('day')])){
				$data[$private->f('day')] = array('active'=>0,'private'=>0,'idle'=>0,'days' => 0);
			}
			$data[$private->f('day')]['private'] = $private->f('duration') / 3600 ;
			$data[$private->f('day')]['days'] = $totals[$private->f('day')];
		}
		
		$days = array($ft->lookup('Sunday'),$ft->lookup('Monday'),$ft->lookup('Tuesday'),$ft->lookup('Wednesday'),$ft->lookup('Thursday'),$ft->lookup('Friday'),$ft->lookup('Saturday'));
		
		for($i = 0; $i< 7; $i++){
		
			$proc_active = $proc_overtime = $proc_idle = $proc_private = 0;

			$proc_active = $data[$i]['active'] ? $data[$i]['active'] : 0;
			$proc_idle = $data[$i]['idle'] ? $data[$i]['idle'] : 0;
			$proc_overtime = $data[$i]['overtime'] ? $data[$i]['overtime'] : 0;
			$proc_private = $data[$i]['private'] ? $data[$i]['private'] : 0;
			
			if($proc_active ==  0 && $proc_idle ==  0  && $proc_overtime ==  0 && $proc_private ==  0)
			{
				continue;
			}
				
			$ft->assign('DAY',$days[$i]);
			
			$ft->assign('WEEK_ACTIVE',number_format($proc_active,2));
			$ft->assign('WEEK_IDLE',number_format($proc_idle,2));
			$ft->assign('WEEK_OVERTIME',number_format($proc_overtime,2));
			$ft->assign('WEEK_PRIVATE',number_format($proc_private,2));
			$ft->parse('WEEK_SUMMARY_ROW_OUT','.week_summary_row');
		}
		
		// application usage
		
		$session = $this->dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
		INNER JOIN session ON session.session_id = session_application.session_id
		".$app_join."  WHERE 1 = 1 AND session_application.time_type = 0 ". $app_filter);
		
		$total = $session['duration'];
		
		$this->dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name FROM session_application 
		INNER JOIN application ON application.application_id = session_application.application_id
		INNER JOIN session ON session.session_id = session_application.session_id
		".$app_join."
		WHERE session_application.duration > 0 AND 1= 1 AND session_application.time_type = 0 
		".$app_filter."
		GROUP BY session_application.application_id
		ORDER BY app_duration desc");
		$i = 0;
		$tot = 0;
		
		while ($this->dbu->move_next() && $i < 6){
			$proc = ($this->dbu->f('app_duration') * 100 / $total);
			
			$ft->assign(array(
				'APPLICATION' => decode_numericentity($this->dbu->f('name')),
				'PROCENTAGE' => number_format($proc,2,'.',','),
				'TIME_H' => intval(intval($this->dbu->f('app_duration')) / 3600),
				'TIME_M' => (intval($this->dbu->f('app_duration')) / 60) % 60,
				'TIME_S' => intval($this->dbu->f('app_duration')) % 60,
			));
			$tot += $this->dbu->f('app_duration');
			$ft->parse('APPLICATION_SUMMARY_ROW_OUT','.application_summary_row');
			$i++;
		}
		
		if($total != $tot){
			
			$proc = (($total-$tot) * 100 / $total);
			
			$ft->assign(array(
				'APPLICATION' => '[!L!]Others[!/L!]',
				'PROCENTAGE' => number_format($proc,2,'.',','), 
				'TIME_H' => intval(intval($total-$tot) / 3600),
				'TIME_M' => (intval($total-$tot) / 60) % 60,
				'TIME_S' => intval($total-$tot) % 60,
			));
			
			$ft->parse('APPLICATION_SUMMARY_ROW_OUT','.application_summary_row');
		}
		$output_file = $ft->lookup('Overtime').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 		
			if($_SESSION['attachment_name']) 			
			{ 				
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);
			}
			else {
			header('Pragma: public'); 
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents;}
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function overtime_validate(&$ld)                              *
	****************************************************************/
	function overtime_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function fileactivity(&$ld)                                   *
	****************************************************************/
	function file(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->file_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'file.csv'));
		$ft->define_dynamic('file_row','main');
		
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'File Activity',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		if( is_numeric($ld['app']) && ( $ld['app'] != -1 ) ){
			$app_filter .= ' AND fixed = '.$ld['app'];
		}
		

	
	    $pieces = explode('-',$glob['f']);
	    $filterCount = count($pieces);
	    
	    if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){
	    		$onlycompfilter = '';
	    	}else {
	    		$onlycompfilter = 'INNER JOIN computer ON computer.computer_id = session.computer_id';
	    	}
    
	    	$this->dbu->query("SELECT session_file.eventtime, computer.name as computername,session_file.action,file.*,member.logon,member.active, CONCAT(member.first_name,' ',member.last_name) AS memebrname, member.alias FROM session_file
	    		INNER JOIN file ON file.file_id = session_file.file_id
	    		INNER JOIN session ON session.session_id = session_file.session_id
	    		".$onlycompfilter."
	    		".$app_join."
	    		WHERE 1=1 AND session_file.time_type = 0 ".$app_filter."
	    		GROUP by file.file_id,action
	    		ORDER BY session_file.eventtime DESC ".$number_of_rows);
	    				
		$i=0;
		
		while($this->dbu->move_next()){
			$ft->assign(array(
				'NAME' => trialEncrypt($this->dbu->f('alias') == 1 ? decode_numericentity($this->dbu->f('membername')) : decode_numericentity($this->dbu->f('logon'))),
				'COMPUTER' => trialEncrypt($this->dbu->f('computername')),
				'DATE' => date('d/m/y H:i',$this->dbu->f('eventtime')),
				'OPERATION' => $this->dbu->f('action'),
				//'TYPE' => $this->dbu->f('fixed') == 1 ? 'fixed' : 'mobile',
				// 'PATH' => $this->dbu->f('drive').'\\'.str_replace('||','\\',$this->dbu->f('path')),
				'PATH' => str_replace('&#092;&#092;','\\',$this->dbu->f('drive').$this->dbu->f('path')),
			));
			
			// START add new drive type values Alex
			// 0 REMOVABLE   1 FIXED   2 REMOTE   3 CDROM   4 RAMDISK   5 UNKNOWN
			$drivetype = $this->dbu->f('fixed');
			switch ($drivetype){
				case 0:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Removable')
					));
					break;
				case 2:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Remote')
					));
					break;
				case 3:
					$ft->assign(array(
						'TYPE' => $ft->lookup('CDRom')
					));
					break;
				case 4:
					$ft->assign(array(
						'TYPE' => $ft->lookup('RAMDisk')
					));
					break;
				case 5:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Unknown')
					));
					break;
				default:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Fixed')
					));
			// END add new drive type values
			}
		
			$ft->parse('FILE_ROW_OUT','.file_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('File Activity').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 		
			if($_SESSION['attachment_name']) 	
					{ 	
						$tmp = ini_get('upload_tmp_dir'); 		
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 	
			}
			
			else {
				header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function fileactivity_validate(&$ld)                          *
	****************************************************************/
	function file_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function print(&$ld)                                   *
	****************************************************************/
	function csvprint(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->print_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'print.csv'));
		$ft->define_dynamic('print_row','main');
		
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => $ft->lookup('Print Activity'),
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		if( is_numeric($ld['app']) && ( $ld['app'] != -1 ) ){
			$app_filter .= ' AND fixed = '.$ld['app'];
		}
		

	
	    $pieces = explode('-',$glob['f']);
	    $filterCount = count($pieces);
	
	    if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){
	    		$onlycompfilter = '';
	    	}else {
	    		$onlycompfilter = 'INNER JOIN computer ON computer.computer_id = session.computer_id';
	    	}
    
	    	$this->dbu->query("SELECT session_print.eventtime, computer.name as computername, printer.alias as printername, session_print.page_num as pagenum,fileprint.*,member.logon, member.active, CONCAT(member.first_name,' ',member.last_name) AS membername, member.alias FROM session_print
	    		INNER JOIN fileprint ON fileprint.file_id = session_print.file_id
	    		INNER JOIN printer ON printer.printer_id = session_print.printer_id
	    		INNER JOIN session ON session.session_id = session_print.session_id
	    		".$onlycompfilter."
	    		".$app_join."
	    		WHERE 1=1 AND session_print.time_type = 0 " . $app_filter."
	    		ORDER BY session_print.eventtime DESC ".$number_of_rows);
	    				
		$i=0;
		
		while($this->dbu->move_next()){
			$ft->assign(array(
				'NAME' => trialEncrypt($this->dbu->f('alias') == 1 ? decode_numericentity($this->dbu->f('membername')) : decode_numericentity($this->dbu->f('logon'))),
				'COMPUTER' => trialEncrypt($this->dbu->f('computername')),
				'DATE' => date('d/m/y H:i',$this->dbu->f('eventtime')),
				'PAGENUM' => getna($this->dbu->f('pagenum')),
				'PRINTER' => html_entity_decode(urldecode($this->dbu->f('printername'))),
				'PATH' => html_entity_decode(urldecode($this->dbu->f('path')),ENT_QUOTES)
			));
		
			$ft->parse('FILE_ROW_OUT','.print_row');
			$i++;
		}
		
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Print Activity').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 		
			if($_SESSION['attachment_name']) 	
					{ 	
						$tmp = ini_get('upload_tmp_dir'); 		
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 	
			}
			
			else {
				header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function print_validate(&$ld)                          *
	****************************************************************/
	function print_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function usersactivity(&$ld)                                  *
	****************************************************************/
	function usersactivity(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->usersactivity_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'usersactivity.csv'));
		$ft->define_dynamic('ua_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Users Activity',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);

		$matches = array();
		preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$_SESSION['filters']['time']['time'],$matches);
		$pieces = array_shift($matches);
		switch (count($pieces)){
			case 1:
				$avg = 1;
				break;
			case 2:
				$start_time = strtotime(reset($pieces));
				$start_hour = date('G',$start_time);
				$start_time = mktime(0,0,0,date('n',$start_time),date('d',$start_time),date('Y',$start_time));
				//---
				$end_time = strtotime(end($pieces));
				$end_hour = date('G',$end_time);
				$end_time = mktime(23,59,59,date('n',$end_time),date('d',$end_time),date('Y',$end_time));
				$avg = ceil(($end_time - $start_time) / 86400);
				break;
		}
		
		$active = array();
		$this->dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
		INNER JOIN session ON session.session_id = session_activity.session_id
		".$app_join."
		WHERE session_activity.activity_type = 1 ".$app_filter."
		GROUP BY member.member_id");
		while ($this->dbu->move_next()){
			$active[$this->dbu->f('member_id')] += $this->dbu->f('duration');
		}
		
		
		$inactive = array();
		$this->dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
		INNER JOIN session ON session.session_id = session_activity.session_id
		".$app_join."
		WHERE session_activity.activity_type = 0 ".$app_filter."
		GROUP BY member.member_id");
		while ($this->dbu->move_next()){
			$inactive[$this->dbu->f('member_id')] += $this->dbu->f('duration');
		}
		
		$online = array();
		$this->dbu->query("SELECT SUM(session_application.duration) as duration,session.session_id,member.member_id FROM session_application 
		INNER JOIN session ON session.session_id = session_application.session_id
		".$app_join."
		INNER JOIN application ON session_application.application_id = application.application_id
		WHERE 1=1 AND session_application.time_type = 0 AND application.application_type IN (".ONLINE_TIME_INCLUDE.") ".$app_filter."
		GROUP BY member.member_id");
		
		while ($this->dbu->move_next()){
			$online[$this->dbu->f('member_id')] += $this->dbu->f('duration');
		}
		
		//overtime 

		$pieces = split('-',$_SESSION['filters']['f']);
		$pieces[0] = substr($pieces[0],1);
		$workschedule = get_workschedule($pieces[0]);
		
		$overtime = array();
		$this->dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id, session_activity.hour, session_activity.day FROM session_activity 
		INNER JOIN session ON session.session_id = session_activity.session_id
		".$app_join."
		WHERE 1 = 1 AND session_activity.activity_type < 2 ".$app_filter."
		GROUP BY session_activity.hour, session_activity.day, member.member_id");
		
		while ($this->dbu->move_next()){
			if( ($this->dbu->f('hour') < $workschedule[$this->dbu->f('day')]['start_hour'] ) ||( $this->dbu->f('hour') >= $workschedule[$this->dbu->f('day')]['end_hour'] ) )
			{
				$overtime[$this->dbu->f('member_id')] += $this->dbu->f('duration');
			}
		}
		
		$this->dbu->query("SELECT member.first_name,
		member.last_name,
		member.alias,
		member.logon,
		member.active,
		member.member_id,
		session.session_id,
		SUM(session_application.duration+session_application.idle_duration) as duration
		FROM session_application
		INNER JOIN session ON session.session_id = session_application.session_id
		".$app_join."
		WHERE 1=1
		AND session_application.time_type = 0
		".$app_filter."
		GROUP BY member.member_id");
		
		$i=0;
		
		while($this->dbu->move_next()){
			$ft->assign(array(
				'NAME' => trialEncrypt($this->dbu->f('alias') == 1 ? decode_numericentity($this->dbu->f('first_name')).' '.decode_numericentity($this->dbu->f('last_name')) : decode_numericentity($this->dbu->f('logon'))),
				'TOTAL_H' => intval(intval($this->dbu->f('duration')) / 3600),
				'TOTAL_M' => (intval($this->dbu->f('duration')) / 60) % 60,
				'TOTAL_S' => intval($this->dbu->f('duration')) % 60,
				'ACTIVE_H' => intval(intval($active[$this->dbu->f('member_id')]) / 3600),
				'ACTIVE_M' => (intval($active[$this->dbu->f('member_id')]) / 60) % 60,
				'ACTIVE_S' => intval($active[$this->dbu->f('member_id')]) % 60,
				'ACTIVE_AVG_H' => intval(intval($active[$this->dbu->f('member_id')]/$avg) / 3600),
				'ACTIVE_AVG_M' => (intval($active[$this->dbu->f('member_id')]/$avg) / 60) % 60,
				'ACTIVE_AVG_S' => intval($active[$this->dbu->f('member_id')]/$avg) % 60,
				'OVERTIME_H' => intval(intval($overtime[$this->dbu->f('member_id')]) / 3600),
				'OVERTIME_M' => (intval($overtime[$this->dbu->f('member_id')]) / 60) % 60,
				'OVERTIME_S' => intval($overtime[$this->dbu->f('member_id')]) % 60,
				'OVERTIME_AVG_H' => intval(intval($overtime[$this->dbu->f('member_id')]/$avg) / 3600),
				'OVERTIME_AVG_M' => (intval($overtime[$this->dbu->f('member_id')]/$avg) / 60) % 60,
				'OVERTIME_AVG_S' => intval($overtime[$this->dbu->f('member_id')]/$avg) % 60,
				'IDLE_H' => intval(intval($inactive[$this->dbu->f('member_id')]) / 3600),
				'IDLE_M' => (intval($inactive[$this->dbu->f('member_id')]) / 60) % 60,
				'IDLE_S' => intval($inactive[$this->dbu->f('member_id')]) % 60,
				'IDLE_AVG_H' => intval(intval($inactive[$this->dbu->f('member_id')]/$avg) / 3600),
				'IDLE_AVG_M' => (intval($inactive[$this->dbu->f('member_id')]/$avg) / 60) % 60,
				'IDLE_AVG_S' => intval($inactive[$this->dbu->f('member_id')]/$avg) % 60,
				'ONLINE_H' => intval(intval($online[$this->dbu->f('member_id')]) / 3600),
				'ONLINE_M' => (intval($online[$this->dbu->f('member_id')]) / 60) % 60,
				'ONLINE_S' => intval($online[$this->dbu->f('member_id')]) % 60,
				'ONLINE_AVG_H' => intval(intval($online[$this->dbu->f('member_id')]/$avg) / 3600),
				'ONLINE_AVG_M' => (intval($online[$this->dbu->f('member_id')]/$avg) / 60) % 60,
				'ONLINE_AVG_S' => intval($online[$this->dbu->f('member_id')]/$avg) % 60,
				'ACTIVITY_PERCENT' => number_format($active[$this->dbu->f('member_id')] *100 / $this->dbu->f('duration'),2,',','.').'%'
			));
		
			$ft->parse('UA_ROW_OUT','.ua_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Users Activity').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
	
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 		
				{ 					
					$tmp = ini_get('upload_tmp_dir'); 				
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);
				}

			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 

			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function usersactivity_validate(&$ld)                         *
	****************************************************************/
	function usersactivity_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function attendance(&$ld)                                     *
	****************************************************************/
	function attendance(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->attendance_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'attendance.csv'));
		$ft->define_dynamic('ua_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Attendance',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);

		$active = array();
		$this->dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
		INNER JOIN session ON session.session_id = session_activity.session_id
		INNER JOIN member on member.member_id = session.member_id
		WHERE session_activity.activity_type = 1
		".$app_filter."
		GROUP BY session_id,member.member_id");
		while ($this->dbu->move_next()){
			$active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] += $this->dbu->f('duration');
		}
		
		$inactive = array();
		$this->dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
		INNER JOIN session ON session.session_id = session_activity.session_id
		INNER JOIN member on member.member_id = session.member_id
		WHERE session_activity.activity_type = 0
		".$app_filter."
		GROUP BY session_id,member.member_id");
		while ($this->dbu->move_next()){
			$inactive[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] += $this->dbu->f('duration');
		}
		
		$private = array();
		$this->dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
		INNER JOIN session ON session.session_id = session_activity.session_id
		INNER JOIN member on member.member_id = session.member_id
		WHERE session_activity.activity_type > 1
		".$app_filter."
		GROUP BY session_id,member.member_id");
		while ($this->dbu->move_next()){
			$private[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] += $this->dbu->f('duration');
		}
		
		//overtime 
		
		$pieces = split('-',$_SESSION['filters']['f']);
		$pieces[0] = substr($pieces[0],1);
		$workschedule = get_workschedule($pieces[0]);
		
		$overtime = array();
		$this->dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id, session_activity.hour, session_activity.day FROM session_activity 
		INNER JOIN session ON session.session_id = session_activity.session_id
		INNER JOIN member on member.member_id = session.member_id
		WHERE 1= 1 
		AND session_activity.activity_type = 1
		".$app_filter."
		GROUP BY session_activity.hour, session_activity.day, member.member_id");
		
		while ($this->dbu->move_next()){
			if( ($this->dbu->f('hour') < $workschedule[$this->dbu->f('day')]['start_hour'] ) ||( $this->dbu->f('hour') >= $workschedule[$this->dbu->f('day')]['end_hour'] ) )
			{
				$overtime[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] += $this->dbu->f('duration');
			}
		}
	
	    if($glob['first_last_only'] == 1){
	    	$_SESSION['first_last_only'] = 1;
	    }
	    if($glob['first_last_only'] == 2){
	    	$_SESSION['first_last_only'] = 1;
	    	// unset($_SESSION['first_last_only']);
	    }
	    $consider_first_last_action=1;
	    if($_SESSION['first_last_only']){
	    	$consider_first_last_action = 1;
	    	$ft->assign('FIRST_LAST_ONLY_VAL', 2);
	    	$ft->assign('FIRST_LAST_ONLY_CHECKED', 'checked');
	    } else {
	    	$ft->assign('FIRST_LAST_ONLY_VAL', 1);
	    	$ft->assign('FIRST_LAST_ONLY_CHECKED', '');
	    	
	    }
    
	    if($consider_first_last_action==1){
	    	$secondary_condition = "AND session_attendance.active = 1";
	    } else {
	    	$secondary_condition = "AND session_attendance.active < 2";
	    }
    
	    $_SESSION['first_last_only'] = 1;
	    $secondary_condition = "AND session_attendance.active = 1";
    
	    $pieces = explode('-',$glob['f']);
	    $filterCount = count($pieces);
	    
	    if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){
	    	$onlycompfilter = '';
	    }else {
	    	$onlycompfilter = '
	    INNER JOIN computer2member ON computer2member.member_id = member.member_id
	    INNER JOIN computer ON computer.computer_id = session.computer_id';
	    }
	    	
	    $this->dbu->query("SELECT member.first_name,
	    member.last_name,
	    member.alias,
	    member.member_id,
	    member.logon,
	    session_activity.activity_type,
	    computer.name AS computer_name,
	    session.day,
	    session_activity.session_id,
	    session.member_id,
	    MIN(session_attendance.start_time) AS start_work,
	    MAX(session_attendance.end_time) AS end_work
	    FROM session_activity
	    INNER JOIN session ON session.session_id = session_activity.session_id
	    ".$app_join."
	    INNER JOIN session_attendance ON session_attendance.session_id = session_activity.session_id
	    ".$onlycompfilter."
	    WHERE 1=1
	    ".$secondary_condition."
	    AND session_attendance.start_time >= session.date
	    ".$app_filter."
	    GROUP BY member.member_id,session_activity.session_id");
	    
	    $i=0;
	    $ai = 0;
	    $ttl_active = 0;
	    $ttl_start = 0;
	    $ttl_end = 0;
	    $ttl_total = 0;
	    $ttl_avg = 0;
	    while($this->dbu->move_next()){
	    	$ttl_active = $ttl_active + ($active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')]);
	    $ttl_start = $ttl_start + date('H',$this->dbu->f('start_work')) * 60 * 60 + date('i',$this->dbu->f('start_work')) * 60;
	    $ttl_end = $ttl_end + date('H',$this->dbu->f('end_work')) * 60 * 60 + date('i',$this->dbu->f('end_work')) * 60;
		$ttl_total = $ttl_total + ($active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] + $inactive[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] + $private[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')]);
		$ttl_ratio = $ttl_ratio + (((($active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')]) * 100) / ($this->dbu->f('end_work') - $this->dbu->f('start_work'))));
		$ai++;
		$ft->assign(array(
			'NAME' => $this->dbu->f('alias') == 1 ? $this->dbu->f('first_name').' '.$this->dbu->f('last_name') : $this->dbu->f('logon'),
			'COMPUTER' => $this->dbu->f('computer_name'),
			'DATE' => date('d/m/Y - D',$this->dbu->f('start_work')),
			'START' => date('H:i',$this->dbu->f('start_work')),
			'END' => date('H:i',$this->dbu->f('end_work')),
			'TOTAL' => format_time($active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] + $inactive[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] + $private[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')],true,true),
			'ACTIVE' => format_time($active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')],true,true),
			'IDLE' => format_time($inactive[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')],true,true),
			'OVERTIME' => format_time($overtime[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')]),
			'PRIVATE' => format_time($private[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')]),
			'RATIO' => number_format(((($active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')]) * 100) / ($this->dbu->f('end_work') - $this->dbu->f('start_work'))),2),
			
		));
		
		if($consider_first_last_action==1){
			$total = $this->dbu->f('end_work') - $this->dbu->f('start_work');
			$new_iddle = $total - $active[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')] - $private[$this->dbu->f('member_id').'-'.$this->dbu->f('session_id')];
			$ft->assign('IDLE', format_time($new_iddle,true,true));
			$ft->assign('TOTAL', format_time($total,true,true));
		}
		
		$ft->parse('UA_ROW_OUT','.ua_row');
		$i++;
    	}
		if($ai != 0){
			$ft->assign(array(
				'AVG_ACTIVE' => format_time($ttl_active / $ai,true,true),
				'AVG_START' => format_time($ttl_start / $ai,false,true),
				'AVG_END' => format_time($ttl_end / $ai,false,true),
				'AVG_TOTAL' => format_time($ttl_total / $ai,true,true),
				'AVG_RATIO' => number_format($ttl_ratio / $ai,2),
				
			));
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Attendance').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
	
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents);
			if($_SESSION['attachment_name'])
				{
						$tmp = ini_get('upload_tmp_dir');
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
				}
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function attendance_validate(&$ld)                            *
	****************************************************************/
	function attendance_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function applicationusageaggregated(&$ld)                     *
	****************************************************************/
	function applicationusageaggregated(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->applicationusageaggregated_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'applicationusageaggregated.csv'));
		$ft->define_dynamic('template_row','main');
				
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'Application Usage / Aggregated',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],'all');
		
		$session = $this->dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."  WHERE 1 = 1 
			AND session_application.time_type = 0
			". $app_filter);
		
		$total = $session['duration'];

		$application = $this->dbu->query("SELECT SUM(session_application.duration) as app_duration,
			application.description as name, 
			session_application.application_id,
			application.application_type,
			COALESCE(application_productivity.productive,1) AS productive
			FROM session_application 
			INNER JOIN application ON application.application_id = session_application.application_id
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."
			LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id 
			AND application_productivity.link_id = application.application_id 
			AND application_productivity.link_type = 0
			WHERE session_application.duration > 0 
			AND session_application.time_type = 0
			".$app_filter."
			GROUP BY session_application.application_id
			ORDER BY app_duration desc ".$number_of_rows);
		
		$i = 0;
		
		while ($application->next()){
			
			$proc = ($application->f('app_duration') * 100 / $total);
			
			$cat_name = $ft->lookup('Uncategorised');
			$cat_id = 1;
			if(isset($categories[$application->f('application_id').'-0'])){
				$cat_name = $ft->lookup($categories[$application->f('application_id').'-0']['category']);
				$cat_id = $categories[$application->f('application_id').'-0']['category_id'];
			}
			else if(isset($categories[$application->f('application_id').'-1'])){
					$cat_name = $ft->lookup($categories[$application->f('application_id').'-1']['category']);
					$cat_id = $categories[$application->f('application_id').'-1']['category_id'];
				}
				else if(isset($categories[$application->f('application_id').'-2'])){
						$cat_name = $ft->lookup($categories[$application->f('application_id').'-2']['category']);
						$cat_id = $categories[$application->f('application_id').'-2']['category_id'];
					}
					else if(isset($categories[$application->f('application_id').'-3'])){
						$cat_name = $ft->lookup($categories[$application->f('application_id').'-3']['category']);
						$cat_id = $categories[$application->f('application_id').'-3']['category_id'];
					}
			
			$ft->assign(array(
				'APPLICATION' => decode_numericentity($application->f('name')),
				'PERCENT' => number_format($proc,2,',','.'),
				'TIME_H' => intval(intval($application->f('app_duration')) / 3600),
				'TIME_M' => (intval($application->f('app_duration')) / 60) % 60,
				'TIME_S' => intval($application->f('app_duration')) % 60,
				'CATEGORY' => decode_numericentity($cat_name),
			));
			
			/*switch ($application->f('productive')){
				case 0:
					$ft->assign(array(
						'TYPE'=> 'distracting',
					));
					break;			
				case 2:
					$ft->assign(array(
						'TYPE'=> 'productive',
						
					));
					break;
				case 3:
					$ft->assign(array(
						'TYPE' => 'productive',
						// 'PERCENT' => '',
					));
					break;
				default:
					$ft->assign(array(
						'TYPE'=> 'neutral',
					));
			}*/
			
			$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			$i++;
		}

		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Application Usage (Aggregated)').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
	
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 		
			if($_SESSION['attachment_name']) 			{ 				
					$tmp = ini_get('upload_tmp_dir'); 		
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);
					}

			else {
			header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 

			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function applicationusageaggregated_validate(&$ld)            *
	****************************************************************/
	function applicationusageaggregated_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function document(&$ld)                                       *
	****************************************************************/
	function document(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->document_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'document.csv'));
		$ft->define_dynamic('document_row','main');
		
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
				$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'Document Monitoring',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		//build application
		$apps = $this->dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive FROM application 
		LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
		AND application_productivity.link_type = 0
		WHERE application.application_type = 2");
		$ddr = array();
		$apps_productivity = array();
		$i = 0;
		while ($apps->next()){
			if($i < 15)
			{
				$ddr[$apps->f('application_id')] = $apps->f('description');
			}
			$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
			$i++;
		}
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],2);
		
		if($ld['app'] && $ld['app'] != -1 && in_array($ld['app'],array_keys($ddr))){
			$total_filter .= ' AND application_id ='.$ld['app'];
			$app_filter .= ' AND session_document.application_id = '.$ld['app'];
		}
		
        $total = $this->dbu->field("SELECT SUM(session_document.duration) FROM session_document 
        					INNER JOIN session ON session.session_id = session_document.session_id
        					".$app_join."
        WHERE 1=1 AND session_document.time_type = 0 ".$app_filter);

		$document = $this->dbu->query("SELECT SUM(session_document.duration) as duration,document.name,document.document_id,
		COALESCE(application_productivity.productive,1) AS productive,
		document.application_id FROM session_document
		INNER JOIN document ON document.document_id = session_document.document_id
		INNER JOIN session ON session.session_id = session_document.session_id
		".$app_join."
		LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id 
		AND application_productivity.link_id = document.document_id 
		AND application_productivity.link_type = 2
		WHERE 1=1 AND session_document.time_type = 0 ".$app_filter."
		AND session_document.duration > 0
		GROUP BY document.document_id
		HAVING duration > 0
		ORDER BY duration DESC ".$number_of_rows);
		
		$i=0;
				
		while($document->next()){
			
			$cat_name = $ft->lookup('Uncategorised');
			
			
			if(isset($categories[$document->f('document_id').'-2'])){
				$cat_name = $ft->lookup($categories[$document->f('document_id').'-2']['category']);
			}
			
			$ft->assign(array(
				'DOCUMENT' => decode_numericentity($document->f('name')),
				'CATEGORY' => decode_numericentity($cat_name),
				'TIME_H' => intval(intval($document->f('duration')) / 3600),
				'TIME_M' => (intval($document->f('duration')) / 60) % 60,
				'TIME_S' => intval($document->f('duration')) % 60,
				'WIDTH'  => ((($document->f('duration') * 100) / $total) > 1) ? number_format((($document->f('duration') * 100) / $total),2,',','.') : ' < 1',
			));
			
			/*$productive = $document->f('productive');
			
			if($apps_productivity[$document->f('application_id')] != 3){
				$productive = $apps_productivity[$document->f('application_id')];
			}
		
			switch ($productive){
				case 0://distracting
					$ft->assign(array(
						'TYPE' => 'distracting'
					));
					break;
				case 2:
					$ft->assign(array(
						'TYPE' => 'productive'
					));
					break;
				default:
					$ft->assign(array(
						'TYPE' => 'neutral'
					));
						
			}*/
				
			$ft->parse('DOCUMENT_ROW_OUT','.document_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Document Monitoring').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 			{ 		
				$tmp = ini_get('upload_tmp_dir'); 			
				file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
				}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function document_validate(&$ld)                              *
	****************************************************************/
	function document_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function interneturls(&$ld)                                   *
	****************************************************************/
	function interneturls(&$ld)
	{
		$this->dbu = new mysql_db();
		$session_website = get_session_website_table();
		if(!$this->interneturls_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'interneturls.csv'));
		$ft->define_dynamic('internet_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'Internet Activity (Links)',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		//build application
		$apps = $this->dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive FROM application 
			LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
			AND application_productivity.link_type = 0
			WHERE application.application_type = 3");
		
		$apps_productivity = array();
		$ddr = array();
		
		while ($apps->next()){
			$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
			$ddr[$apps->f('application_id')] = $apps->f('description');
		}
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],3);
		
		if($ld['app'] && $ld['app'] != -1 && in_array($ld['app'],array_keys($ddr))){
			$total_filter .= ' AND application_id ='.$ld['app'];
			$app_filter .= ' AND session_website.application_id = '.$ld['app'];
		}
		
		$domain_productivity = array();
		
		$domains = $this->dbu->query("SELECT * FROM domain
			INNER JOIN application_productivity ON application_productivity.link_id = domain.domain_id
			AND application_productivity.link_type = 3");
		
		while ($domains->next()){
			$domain_productivity[$domains->f('domain_id')] = $domains->f('productive');
		}
		
        //calculate the total
        $total = $this->dbu->field("SELECT SUM(session_" . $session_website . ".duration) FROM session_" . $session_website . "
        INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
        ".$app_join." WHERE 1=1 ".$app_filter);
		
		$internet = $this->dbu->query("SELECT SUM(session_website.duration) as duration,
			website.url,
			session_website.domain_id,
			website.website_id,
			website.application_id
			FROM session_website
			INNER JOIN website ON website.website_id = session_website.website_id
			INNER JOIN session ON session.session_id = session_website.session_id
			".$app_join."
			WHERE 7=7 AND session_website.time_type = 0 ".$app_filter."
			AND session_website.application_id = website.application_id
			GROUP BY session_website.website_id
			HAVING duration > 0
			ORDER BY duration desc ".$number_of_rows);
		
		$i=0;
				
		while($internet->next()){
			
			if($internet->f('duration') == 0)
			{
				continue;
			}
			
			$cat_name = $ft->lookup('Uncategorised');
	
		
			if(isset($categories[$internet->f('website_id').'-3'])){
				$cat_name = $categories[$internet->f('website_id').'-3']['category'];
			}
			
			$ft->assign(array(
				'WEBPAGE' =>$internet->f('url'),
				'CATEOGRY' => decode_numericentity($cat_name),
				'TIME_H' => intval(intval($internet->f('duration')) / 3600),
				'TIME_M' => (intval($internet->f('duration')) / 60) % 60,
				'TIME_S' => intval($internet->f('duration')) % 60,
				'WIDTH'  => ((($internet->f('duration') * 100) / $total) > 1) ? number_format((($internet->f('duration') * 100) / $total),2,',','.') : ' < 1',
			));
			
			$productive = isset($domain_productivity[$internet->f('domain_id')]) ? $domain_productivity[$internet->f('domain_id')] : 1;

			if($apps_productivity[$internet->f('application_id')] != 3 && !isset($domain_productivity[$internet->f('domain_id')])){
				$productive = $apps_productivity[$internet->f('application_id')];
			}
			
			switch ($productive){
				case 0://distracting
					$ft->assign(array(
						'TYPE' => $ft->lookup('Distracting')
					));
					break;
				case 2:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Productive')
					));
					break;
				default:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Neutral')
					));
			}
							
			$ft->parse('INTERNET_ROW_OUT','.internet_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Internet Activity (Links)').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
	
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
				if($_SESSION['attachment_name']) 		
					{ 				
					$tmp = ini_get('upload_tmp_dir'); 	
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function interneturls_validate(&$ld)                          *
	****************************************************************/
	function interneturls_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	
	
	/****************************************************************
	* function internetwindows(&$ld)                                *
	****************************************************************/
	function internetwindows(&$ld)
	{
		$this->dbu = new mysql_db();
		$session_website = get_session_website_table();
		if(!$this->internetwindows_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'internetwindows.csv'));
		$ft->define_dynamic('internet_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'Internet Activity (Page Titles)',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		//build application
		$apps = $this->dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive FROM application 
			LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
			AND application_productivity.link_type = 0
			WHERE application.application_type = 3");
		
		$apps_productivity = array();
		$ddr = array();
		
		while ($apps->next()){
			$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
			$ddr[$apps->f('application_id')] = $apps->f('description');
		}
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],3);
		
		if($ld['app'] && $ld['app'] != -1 && in_array($ld['app'],array_keys($ddr))){
			$total_filter .= ' AND application_id ='.$ld['app'];
			$app_filter .= ' AND session_website.application_id = '.$ld['app'];
		}
		
		$domain_productivity = array();
		
		$domains = $this->dbu->query("SELECT * FROM domain
			INNER JOIN application_productivity ON application_productivity.link_id = domain.domain_id
			AND application_productivity.link_type = 3");
		
		while ($domains->next()){
			$domain_productivity[$domains->f('domain_id')] = $domains->f('productive');
		}
		
        //calculate the total
        $total = $this->dbu->field("SELECT SUM(session_" . $session_website . ".duration) FROM session_" . $session_website . "
        INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
        ".$app_join." WHERE 1=1 ".$app_filter);
		
		$internet = $this->dbu->query("SELECT SUM(session_website.duration) as duration,
			website.url,
			session_website.domain_id,
			website.website_id,
			window.name,
			website.application_id
			FROM session_website
			INNER JOIN window ON window.window_id = session_website.window_id
			INNER JOIN website ON website.website_id = session_website.website_id
			INNER JOIN session ON session.session_id = session_website.session_id
			".$app_join."
			WHERE 7=7 AND session_website.time_type = 0 ".$app_filter."
			AND session_website.application_id = website.application_id
			GROUP BY session_website.window_id
			HAVING duration > 0
			ORDER BY duration desc ".$number_of_rows);
		
		$i=0;
				
		while($internet->next()){
			
			if($internet->f('duration') == 0)
			{
				continue;
			}
			
			$cat_name = $ft->lookup('Uncategorised');
		
			if(isset($categories[$internet->f('website_id').'-3'])){
				$cat_name = $categories[$internet->f('website_id').'-3']['category'];
			}
			
			$ft->assign(array(
				'WEBPAGE' => decode_numericentity($internet->f('name')),
				'CATEOGRY' => decode_numericentity($cat_name),
				'TIME_H' => intval(intval($internet->f('duration')) / 3600),
				'TIME_M' => (intval($internet->f('duration')) / 60) % 60,
				'TIME_S' => intval($internet->f('duration')) % 60,
				'WIDTH'  => ((($internet->f('duration') * 100) / $total) > 1) ? number_format((($internet->f('duration') * 100) / $total),2,',','.') : ' < 1',
			));
			
			$productive = isset($domain_productivity[$internet->f('domain_id')]) ? $domain_productivity[$internet->f('domain_id')] : 1;

			if($apps_productivity[$internet->f('application_id')] != 3 && !isset($domain_productivity[$internet->f('domain_id')])){
				$productive = $apps_productivity[$internet->f('application_id')];
			}
			
			switch ($productive){
				case 0://distracting
					$ft->assign(array(
						'TYPE' => $ft->lookup('Distracting')
					));
					break;
				case 2:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Productive')
					));
					break;
				default:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Neutral')
					));
			}
							
			$ft->parse('INTERNET_ROW_OUT','.internet_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Internet Activity (Page Titles)').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
				if($_SESSION['attachment_name']) 		
					{ 					
						$tmp = ini_get('upload_tmp_dir'); 
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }	
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function internetwindows_validate(&$ld)                       *
	****************************************************************/
	function internetwindows_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	
	/****************************************************************
	* function internetdomins(&$ld)                                 *
	****************************************************************/
	function internetdomains(&$ld)
	{
		$this->dbu = new mysql_db();
		$session_website = get_session_website_table();
		if(!$this->internetdomains_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'internetdomains.csv'));
		$ft->define_dynamic('internet_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'Internet Activity (Domains)',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		//build application
		$apps = $this->dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive,application_productivity.application_productivity_id,application_productivity.department_id FROM application 
						LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
						AND application_productivity.link_type = 0
						WHERE application.application_type = 3" );
		
		$apps_productivity = array();
		$ddr = array();
		$apps_productivity_id = array();
		while ($apps->next()){
			$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
			$ddr[$apps->f('application_id')] = $apps->f('description');
			if(!array_key_exists($apps->f('department_id'),$apps_productivity_id))
				$apps_productivity_id[$apps->f('department_id')]=($apps->f('application_productivity_id')?$apps->f('application_productivity_id'):0);
		}
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],3);
		
		if($ld['app'] && $ld['app'] != -1 && in_array($ld['app'],array_keys($ddr))){
			$total_filter .= ' AND application_id ='.$ld['app'];
			$app_filter .= ' AND session_website.application_id = '.$ld['app'];
		}
		
		$sortable_columns = array(
			'duration',
			'domain.domain',
			'productive',
			);

		$sortcolumns = get_sorting($sortable_columns,'','desc');
	

        //calculate the total
        $total = $this->dbu->field("SELECT SUM(session_" . $session_website . ".duration) FROM session_" . $session_website . "
        INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
        ".$app_join." WHERE 1=1 ".$app_filter);
		
		$internet = $this->dbu->query("SELECT SUM(session_" . $session_website . ".duration) AS duration,
								domain.domain,
								session_" . $session_website . ".domain_id,
								session_" . $session_website . ".application_id,
								COALESCE(application_productivity.productive,1) AS productive,
								member.department_id AS department_id
								FROM session_" . $session_website . "
								INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id
								INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
								".$app_join."
								LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id
								AND application_productivity.link_id = domain.domain_id
								AND application_productivity.link_type = 3
								WHERE 1=1
								".$app_filter."
								GROUP BY session_" . $session_website . ".domain_id
								HAVING duration > 0
								" . $sortcolumns . " ".$number_of_rows);
		
		$i=0;
				
		while($internet->next()){
			
			if($internet->f('duration') == 0)
			{
				continue;
			}
			
			$cat_name = $ft->lookup('Uncategorised');
			
			if(isset($categories[$internet->f('domain_id').'-3'])){
				$cat_name = $categories[$internet->f('domain_id').'-3']['category'];
			}
			
			
			
			$ft->assign(array(
				'WEBPAGE' =>$internet->f('domain'),
				'CATEGORY' => decode_numericentity($cat_name),
				'TIME_H' => intval(intval($internet->f('duration')) / 3600),
				'TIME_M' => (intval($internet->f('duration')) / 60) % 60,
				'TIME_S' => intval($internet->f('duration')) % 60,
		'WIDTH'  => ((($internet->f('duration') * 100) / $total) > 1) ? number_format((($internet->f('duration') * 100) / $total),2,',','.') : ' < 1',
			));
			
	    $dbu_prod = new mysql_db();
	    $productive = $dbu_prod->field("SELECT `productive`
	    					FROM `application_productivity`
	    					WHERE `department_id` = 1
	    					AND `link_id` = " . $internet->f('domain_id') . "
	    					AND `link_type` = 3
	    					LIMIT 1 ");
	    if ($productive === false){
	    	$productive = 1;
	    }
			
			switch ($productive){
				case 0://distracting
					$ft->assign(array(
						'TYPE' => $ft->lookup('Distracting')
					));
					break;
				case 2:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Productive')
					));
					break;
				default:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Neutral')
					));
			}
							
			$ft->parse('INTERNET_ROW_OUT','.internet_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Internet Activity (Domains)').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 
			if($_SESSION['attachment_name']) 		
					{ 					
						$tmp = ini_get('upload_tmp_dir'); 
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function internetdomanins_validate(&$ld)                      *
	****************************************************************/
	function internetdomains_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	
	
	

	/****************************************************************
	* function chat(&$ld)                                           *
	****************************************************************/
	function chat(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->chat_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'chat.csv'));
		$ft->define_dynamic('chat_row','main');
		
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'Chat Monitoring',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],1);
		
		//build application
		
		$apps = $this->dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive FROM application 
		LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
		AND application_productivity.link_type = 0
		WHERE application.application_type = 1");
		$ddr = array();
		$apps_productivity = array();
		while ($apps->next()){
			$ddr[$apps->f('application_id')] = $apps->f('description');
			$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
		}
		
		if($ld['app'] && $ld['app'] != -1 && in_array($ld['app'],array_keys($ddr))){
			$total_filter .= ' AND session_chat.application_id ='.$ld['app'];
			$app_filter .= ' AND session_chat.application_id = '.$ld['app'];
		}
		$total = $this->dbu->field("SELECT SUM(session_chat.duration) FROM session_chat
        INNER JOIN session ON session.session_id = session_chat.session_id
        ".$app_join." WHERE 1=1 AND session_chat.time_type = 0 ".$app_filter);
 		$chat = $this->dbu->query("SELECT SUM(session_chat.duration) as duration,
			chat.name,
			chat.chat_id,
			COALESCE(application_productivity.productive,1) AS productive,
			chat.application_id,
			session_chat.application_id
			FROM session_chat
			INNER JOIN chat ON chat.chat_id = session_chat.chat_id
			INNER JOIN session ON session.session_id = session_chat.session_id
			".$app_join."
			LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id 
			AND application_productivity.link_id = chat.chat_id 
			AND application_productivity.link_type = 1
			WHERE 1=1 AND session_chat.time_type = 0 ".$app_filter."
			GROUP BY chat.chat_id
			HAVING duration > 0
			ORDER BY duration desc ".$number_of_rows);
 		
		$i=0;

		while($chat->next()){
			$cat_name = $ft->lookup('Uncategorised');
	
			if(isset($categories[$chat->f('chat_id').'-1'])){
				$cat_name = $categories[$chat->f('chat_id').'-1']['category'];
			}
			
			$ft->assign(array(
				'SCREENNAME' => decode_numericentity($chat->f('name')),
				'CATEOGRY' => decode_numericentity($cat_name),
				'TIME_H' => intval(intval($chat->f('duration')) / 3600),
				'TIME_M' => (intval($chat->f('duration')) / 60) % 60,
				'TIME_S' => intval($chat->f('duration')) % 60,
		'WIDTH'  => ((($chat->f('duration') * 100) / $total) > 1) ? number_format((($chat->f('duration') * 100) / $total),2,',','.') : ' < 1',
			));
				
			/* $productive = $chat->f('productive');
			if($apps_productivity[$chat->f('application_id')] != 3){
				$productive = $apps_productivity[$chat->f('application_id')];
			}
			
			switch ($productive){
				case 0://distracting
					$ft->assign(array(
						'TYPE' => 'distracting'
					));
					break;
				case 2:
					$ft->assign(array(
						'TYPE' => 'productive'
					));
					break;
				default:
					$ft->assign(array(
						'TYPE' => 'neutral'
					));
			}*/	
			$ft->parse('CHAT_ROW_OUT','.chat_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Chat Monitoring').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 		
					{ 					
						$tmp = ini_get('upload_tmp_dir'); 
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }	
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function chat_validate(&$ld)                                  *
	****************************************************************/
	function chat_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function applicationforms(&$ld)                               *
	****************************************************************/
	function applicationforms(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->applicationforms_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'applicationforms.csv'));
		$ft->define_dynamic('applicationforms_row','main');
		
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		
		$ft->assign(array(
			'TITLE' => 'Application Forms',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		
		
		//build application
		$apps = $this->dbu->query("SELECT
			SUM(session_log.duration) AS duration,
			window.name,
			window.application_id,
			application.description,
			window.window_id FROM session_log
			INNER JOIN window ON window.window_id = session_log.window_id
			INNER JOIN application ON application.application_id = window.application_id
			INNER JOIN session ON session.session_id = session_log.session_id
			".$app_join."
			WHERE 1=1 
			AND session_log.active < 2
			".$app_filter."
			GROUP BY session_log.application_id
			ORDER BY duration desc
			LIMIT 15");
		
		$ddr = array();
		
		while ($apps->next()){
			$ddr[$apps->f('application_id')] = $apps->f('description');
		}
				
		if($ld['app'] && $ld['app'] != -1 && in_array($ld['app'],array_keys($ddr))){
			$total_filter .= ' AND application_id ='.$ld['app'];
			$app_filter .= ' AND session_log.application_id = '.$ld['app'];
		}
		
		//calculate the total
		$total = $this->dbu->field("SELECT SUM(session_log.duration) FROM session_log
					INNER JOIN session ON session.session_id = session_log.session_id
					".$app_join." WHERE 1=1 AND session_log.active < 2 ".$app_filter);
		
		$total = $total ? $total : 1;

		
		
		$all_time= array();
		$this->dbu->query("SELECT
		SUM(session_log.duration) AS duration,
		window.name,
		window.application_id,
		window.window_id FROM session_log
		INNER JOIN window ON window.window_id = session_log.window_id
		INNER JOIN session ON session.session_id = session_log.session_id
		".$app_join."
		WHERE 1=1 AND session_log.active < 2 ".$app_filter."
		GROUP BY session_log.window_id
		ORDER BY duration desc");
		
		while ($this->dbu->move_next())
		{
			$all_time[$this->dbu->f('window_id')] = $this->dbu->f('duration');
		}
		
		
		$applicationforms = $this->dbu->query("SELECT
			SUM(session_log.duration) AS duration,
			window.name,
			window.application_id,
			window.window_id FROM session_log
			INNER JOIN window ON window.window_id = session_log.window_id
			INNER JOIN session ON session.session_id = session_log.session_id
			".$app_join."
			WHERE 1=1 AND session_log.active =1 ".$app_filter."
			GROUP BY session_log.window_id
			ORDER BY duration desc ".$number_of_rows);		
		
		$i=0;
				
		while($applicationforms->next()){
			if(!$applicationforms->f('duration'))
			{
				break;
			}
			$ft->assign(array(
				'WINDOWNAME' => decode_numericentity($applicationforms->f('name')),
				
				'TOTAL_H' => intval(intval($all_time[$applicationforms->f('window_id')]) / 3600),
				'TOTAL_M' => (intval($all_time[$applicationforms->f('window_id')]) / 60) % 60,
				'TOTAL_S' => intval($all_time[$applicationforms->f('window_id')]) % 60,
				
				'ACTIVE_H' => intval(intval($applicationforms->f('duration')) / 3600),
				'ACTIVE_M' => (intval($applicationforms->f('duration')) / 60) % 60,
				'ACTIVE_S' => intval($applicationforms->f('duration')) % 60,
				
				'IDLE_H' => intval(intval($all_time[$applicationforms->f('window_id')] - $applicationforms->f('duration')) / 3600),
				'IDLE_M' => (intval($all_time[$applicationforms->f('window_id')] - $applicationforms->f('duration')) / 60) % 60,
				'IDLE_S' => intval($all_time[$applicationforms->f('window_id')] - $applicationforms->f('duration')) % 60,
			));
				
			$ft->parse('APPLICATIONFORMS_ROW_OUT','.applicationforms_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Application Forms').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 		
					{ 					
						$tmp = ini_get('upload_tmp_dir'); 
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function applicationforms_validate(&$ld)                      *
	****************************************************************/
	function applicationforms_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function productivityreport(&$ld)                             *
	****************************************************************/
	function productivityreport(&$ld)
	{
		$this->dbu = new mysql_db();
		global $glob;
		include(CURRENT_VERSION_FOLDER.'php/ajax/xstats.php');
		if(!$this->productivityreport_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'productivityreport.csv'));
		$ft->define_dynamic('template_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Productivity Report',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		
		$sortable_columns = array(
			'app_duration',
			'name',
			'productive',
			);

		$sortcolumns = get_sorting($sortable_columns,'','desc');
		
		$categories = get_categories($_SESSION['filters']['f'],'all');

		$productivity_filter = ' = member.department_id ';

		if(is_array($main_department_ids) && !empty($main_department_ids)){
			$productivity_filter = 'IN ('.join(',',$main_department_ids).')';
		}
		$session_website = get_session_website_table();
		$session = $this->dbu->row("SELECT SUM(session_application.duration) AS duration FROM session_application
						INNER JOIN session ON session.session_id = session_application.session_id 
						".$app_join."
						WHERE session_application.time_type = 0 AND 1=1 ".$app_filter);
		$total = $session['duration'];
				//get all the apps that are in the current filter
				
		$nodes = explode('-',$glob['f']);
		$department_id = reset($nodes);
		$department_list = get_department_children($department_id,1);
		$querystring = "SELECT     Sum(session_application.duration) AS app_duration, 
                             application.description           AS name, 
                             application.application_type      AS type, 
                             application.application_id, 
                             application_productivity.application_productivity_id AS application_productivity_id,
                             COALESCE(application_productivity.productive,1) AS productive
                  FROM       session_application 
                  INNER JOIN application 
                  ON         application.application_id = session_application.application_id 
					AND      application.application_type != 3 
                  INNER JOIN session 
                  ON         session.session_id = session_application.session_id 
                  ".$app_join."
                  LEFT JOIN  application_productivity 
                  ON         application_productivity.department_id ".$productivity_filter." 
                  AND        application_productivity.link_id = application.application_id 
                  AND        application_productivity.link_type < 3 
                  AND        member.department_id IN (" . $department_list . ") 
                  WHERE      session_application.time_type = 0 
                  AND        2=2 
                  ".$app_filter." 
                  GROUP BY   session_application.application_id 
        union 
         SELECT     sum(session_" . $session_website . ".duration)               AS app_duration,
                               domain.domain                                   AS name, 
                               '3'                                             AS type, 
                               domain.domain_id                                AS application_id,
                               application_productivity.application_productivity_id AS application_productivity_id, 
                               COALESCE(application_productivity.productive,1) AS productive
                    FROM       session_" . $session_website . " 
                    INNER JOIN domain 
                    ON         domain.domain_id = session_" . $session_website . ".domain_id 
                    INNER JOIN session 
                    ON         session.session_id = session_" . $session_website . ".session_id 
                    ".$app_join."
                    LEFT JOIN  application_productivity 
                    ON         application_productivity.department_id ".$productivity_filter." 
                    AND        application_productivity.link_id = domain.domain_id 
                    AND        application_productivity.link_type = 3 
                    AND        member.department_id IN (" . $department_list . ") 
                    WHERE      session_" . $session_website . ".time_type = 0 
                    AND        2=2 
                    ".$app_filter."
                    GROUP BY   session_" . $session_website . ".domain_id
					" . $sortcolumns . " ";

		$application = $dbu->query($querystring);
		
		$i = 0;
		
		while ($application->next()){
			
			$cat_name = $ft->lookup('Uncategorised');
			$cat_id = 1;
			
			if(isset($categories[$application->f('application_id').'-0'])){
				$cat_name = $ft->lookup($categories[$application->f('application_id').'-0']['category']);
				$cat_id = $categories[$application->f('application_id').'-0']['category_id'];
			}
			else if(isset($categories[$application->f('application_id').'-1'])){
					$cat_name = $ft->lookup($categories[$application->f('application_id').'-1']['category']);
					$cat_id = $categories[$application->f('application_id').'-1']['category_id'];
				}
				else if(isset($categories[$application->f('application_id').'-2'])){
						$cat_name = $ft->lookup($categories[$application->f('application_id').'-2']['category']);
						$cat_id = $categories[$application->f('application_id').'-2']['category_id'];
					}
					else if(isset($categories[$application->f('application_id').'-3'])){
						$cat_name = $ft->lookup($categories[$application->f('application_id').'-3']['category']);
						$cat_id = $categories[$application->f('application_id').'-3']['category_id'];
					}
			
			$ft->assign(array(
				'APPLICATION'    => decode_numericentity($application->f('name')),
				'TIME_H' => intval(intval($application->f('app_duration')) / 3600),
				'TIME_M' => (intval($application->f('app_duration')) / 60) % 60,
				'TIME_S' => intval($application->f('app_duration')) % 60,
				'CATEGORY' => decode_numericentity($cat_name),
				'WIDTH'  => ((($application->f('app_duration') * 100) / $total) > 1) ? number_format((($application->f('app_duration') * 100) / $total),2,',','.') : ' < 1',
			));

			switch ($application->f('productive')){
				case 0:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Distracting')
					));
					break;			
				case 2:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Productive')
					));
					break;
				case 3:
					$ft->assign(array(
						'TYPE' => 'mixt'
					));
					break;
				default:
					$ft->assign(array(
						'TYPE' => $ft->lookup('Neutral'),
					));
			}
			
			$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			$i++;
		}
        //	************************************************
        //	****************productivity total**************
        //	************************************************
        $chart = array();
        $prod = array('red', 'rest', 'green');
        
        $session_website = get_session_website_table();
        $p_application = $this->dbu->query("SELECT SUM(session_application.duration) AS duration,
					session_application.application_id,
					application.application_type AS type_id
		FROM session_application
		INNER JOIN application ON application.application_id = session_application.application_id
		INNER JOIN session ON session.session_id = session_application.session_id
		".$app_join."
		WHERE session_application.time_type = 0 AND 1=1
		".$app_filter."
		GROUP BY session_application.application_id");
		//get total for all apps which we can later use to define productivity
		while ($p_application->next()){
			$apps[$p_application->f('application_id')]['total'] = $p_application->f('duration');
			$apps[$p_application->f('application_id')]['type'] = $p_application->f('type_id');
			$chart['total'] += $p_application->f('duration');
		}

		$productivity_filter = ' = member.department_id ';
		$count = 1;
		if(is_array($main_department_ids) && !empty($main_department_ids)){
			$productivity_filter = 'IN ('.join(',',$main_department_ids).')';
			$count = count($main_department_ids);
		}

		//get all the apps that have productivity set for them
		$productivity = $dbu->query("SELECT SUM(session_application.duration) AS app_duration,
									session_application.application_id,
									COALESCE(application_productivity.productive,1) AS productive,
									application_productivity.application_productivity_id,
									(SELECT GROUP_CONCAT(if (application_productivity_id ='', null, application_productivity_id)) FROM application_productivity
									WHERE application_productivity.link_id = application.application_id ) AS parrentaps
									FROM session_application
									INNER JOIN application ON application.application_id = session_application.application_id 
									INNER JOIN session ON session.session_id = session_application.session_id
									".$app_join."
									LEFT JOIN application_productivity ON application_productivity.department_id ".$productivity_filter." 
													   AND application_productivity.link_id = session_application.application_id 
													   AND application_productivity.link_type <= 3
													   
													   AND member.department_id = application_productivity.department_id
									WHERE session_application.time_type = 0 AND 2=2
									".$app_filter."
									GROUP BY session_application.application_id");
			while ($productivity->next()){
				if($productivity->f('productive') == 3){
					//need to see the children for info
					$table = '';
					$primary = '';
					$type_id = $apps[$productivity->f('application_id')]['type'];
					switch ($type_id){
						case 1:
							$session_table = 'chat';
							$table = 'chat';
							break;
						case 2:
							$session_table = 'document';
							$table = 'document';
							break;
						case 3:
							$session_table = $session_website;
							$table = 'domain';
							break;	
						default:
							return '';
					}
					$primary = $table.'_id';		
					$children = $dbu->query("SELECT SUM(session_".$session_table.".duration) AS app_duration,
												application_productivity.productive AS productive
								 FROM session_".$session_table."
								 INNER JOIN session ON session.session_id = session_".$session_table.".session_id
								".$app_join."
								INNER JOIN application_productivity ON application_productivity.department_id = member.department_id  
																		AND application_productivity.link_id = session_".$session_table.".".$primary."
																		AND application_productivity.link_type = ".$type_id."
																		
								WHERE ".(($type_id == 3)?"":"session_".$session_table.".time_type = 0 AND "). "5=5 AND session_".$session_table.".application_id = '".$productivity->f('application_id')."'
								".$app_filter."
								GROUP BY application_productivity.productive
								ORDER BY app_duration DESC");
					
					
					
					while ($children->next()){
						$key = $prod[$children->f('productive')];
						$apps[$productivity->f('application_id')][$key] = $children->f('app_duration');
						$chart[$key] += $children->f('app_duration');
					}
					continue;
				}
				$key = $prod[$productivity->f('productive')];
				$chart[$key] += $productivity->f('app_duration');
			}
		$chart['rest'] = $chart['total'] - (($chart['red'] + $chart['green']));
		$total = $chart['total'];

		if ($total != 0)
		{
			$ft->assign(array(
				'PRODUCTIVE_TOTAL' => number_format($chart['green'] / $total *100,2,'.',','),
				'DISTRACTING_TOTAL' => number_format($chart['red'] / $total * 100, 2,'.',','),
				'NEUTRAL_TOTAL' => number_format($chart['rest'] / $total * 100, 2 ,'.',',')
			));
		}
		
        //	************************************************
        //	************************************************
        //	************************************************
				
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Productivity Report').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 		
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function productivityreport_validate(&$ld)                    *
	****************************************************************/
	function productivityreport_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function categoryactivity(&$ld)                               *
	****************************************************************/
	function categoryactivity(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->categoryactivity_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'categoryactivity.csv'));
		$ft->define_dynamic('categoryactivity_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Activity Categories',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
						
		$sets = get_categories($_SESSION['filters']['f'],'all');
		//pas 1: se selecteaza toate categoriile
		$categories = $this->dbu->query("SELECT * FROM application_category ORDER BY lft ASC");
		$assigns = array();
		$category_durations = array();
		$category_totals = array();
		$i=0;
		$total=0;
		$rights = array();
		while ($categories->next()){
			while (!empty($rights) && (end($rights) < $categories->f('rgt'))){
				array_pop($rights);
			}
			//GRAPH?	
			$assigns[$categories->f('application_category_id')] = array(
				'SPACER'      => str_repeat('&nbsp;&nbsp;&nbsp;',count($rights)),
				'CATEGORY'    => $categories->f('category')=='Uncategorised'? decode_numericentity($ft->lookup('Uncategorised')):decode_numericentity($ft->lookup($categories->f('category'))),
				'TIME'        => 0,
			);
			$rights[] = $categories->f('rgt'); 
			$i++;
		}
		//pas 2: se scot applicatiile
		$app_cats = array();
		$total = 0;
		$applications = $this->dbu->query("SELECT SUM(session_application.duration) AS duration,
							application.application_id
					 FROM session_application 
		 			 INNER JOIN session ON session.session_id = session_application.session_id
		 			 INNER JOIN application ON application.application_id = session_application.application_id
					 ".$app_join."
					 WHERE session_application.duration > 0 
					 AND session_application.time_type = 0 ".$app_filter." 
					 GROUP BY application.application_id");
		while ($applications->next()){
			$cat_id = 1;
			if(isset($sets[$applications->f('application_id').'-0'])){
				$cat_id = $sets[$applications->f('application_id').'-0']['category_id'];
			}
			else if(isset($sets[$applications->f('application_id').'-1'])){
					$cat_id = $sets[$applications->f('application_id').'-1']['category_id'];
				}
				else if(isset($sets[$applications->f('application_id').'-2'])){
						$cat_id = $sets[$applications->f('application_id').'-2']['category_id'];
					}
					else if(isset($sets[$applications->f('application_id').'-3'])){
							$cat_id = $sets[$applications->f('application_id').'-3']['category_id'];
						}
			
			if(!is_array($category_durations[$cat_id])){
				$category_durations[$cat_id] = array();
			}
			
			
			$category_durations[$cat_id][$applications->f('application_id')] = $applications->f('duration');
			$app_cats[$applications->f('application_id')] = $cat_id;
			$total += $applications->f('duration');
			$category_totals[$cat_id] += $applications->f('duration');
		}
		//pas 3: se scot copii
		//pas 3.1 se scot site-urile
		$children = $this->dbu->query("SELECT SUM(session_website.duration) AS duration,
							session_website.domain_id,
							session_website.application_id
					 FROM session_website 
		 			 INNER JOIN session ON session.session_id = session_website.session_id
					 ".$app_join."
					 WHERE session_website.duration > 0 
					 AND session_website.time_type = 0 ".$app_filter." 
					 GROUP BY session_website.domain_id");
		while ($children->next()){
			$cat_id = 1;
			if(isset($sets[$children->f('domain_id').'-3'])){
				$cat_id = $sets[$children->f('domain_id').'-3']['category_id'];
			}
			
			
			if(!is_array($category_durations[$cat_id])){
				$category_durations[$cat_id] = array();
			}
			if(isset($category_durations[$cat_id][$children->f('application_id')])){
				continue;
			}
			
			$category_id = $app_cats[$children->f('application_id')];
			$category_durations[$category_id][$children->f('application_id')] -= $children->f('duration');
			// $category_totals[$category_id] -= $children->f('duration');
			
			$category_durations[$cat_id][$children->f('application_id')] += $children->f('duration');		
			$category_totals[$cat_id] += $children->f('duration');
		}
		
		/*
		//pas 3.2 se scot chat-urile
		$children = $this->dbu->query("SELECT SUM(session_chat.duration) AS duration,
										chat.chat_id,
										chat.application_id
								 FROM session_chat
					 			 INNER JOIN session ON session.session_id = session_chat.session_id
					 			 INNER JOIN chat ON chat.chat_id = session_chat.chat_id
								 ".$app_join."
								 WHERE session_chat.duration > 0 
								 AND session_chat.time_type = 0 ".$app_filter." 
								 GROUP BY chat.chat_id
					           ");
		while ($children->next()){
			$cat_id = 1;
			if(isset($sets[$children->f('chat_id').'-1'])){
				$cat_id = $sets[$children->f('chat_id').'-1']['category_id'];
			}
			
			if(!is_array($category_durations[$cat_id])){
				$category_durations[$cat_id] = array();
			}
			if(isset($category_durations[$cat_id][$children->f('application_id')])){
				continue;
			}
			
			$category_id = $app_cats[$children->f('application_id')];
			$category_durations[$category_id][$children->f('application_id')] -= $children->f('duration');
			// $category_totals[$category_id] -= $children->f('duration');
			
			$category_durations[$cat_id][$children->f('application_id')] += $children->f('duration');		
			$category_totals[$cat_id] += $children->f('duration');
		}
		
		//pas 3.3 se scot documentele
		$children = $this->dbu->query("SELECT SUM(session_document.duration) AS duration,
										document.document_id,
										document.application_id
								 FROM session_document
					 			 INNER JOIN session ON session.session_id = session_document.session_id
					 			 INNER JOIN document ON document.document_id = session_document.document_id
								 ".$app_join."
								 WHERE session_document.duration > 0 
								 AND session_document.time_type = 0 ".$app_filter." 
								 GROUP BY document.document_id
					           ");
		
		while ($children->next()){
			$cat_id = 1;
			if(isset($sets[$children->f('document_id').'-2'])){
				$cat_id = $sets[$children->f('document_id').'-2']['category_id'];
			}
		
			if(!is_array($category_durations[$cat_id])){
				$category_durations[$cat_id] = array();
			}
			if(isset($category_durations[$cat_id][$children->f('application_id')])){
				continue;
			}
			
			$category_id = $app_cats[$children->f('application_id')];
			$category_durations[$category_id][$children->f('application_id')] -= $children->f('duration');
			// $category_totals[$category_id] -= $children->f('duration');
			
			$category_durations[$cat_id][$children->f('application_id')] += $children->f('duration');		
			$category_totals[$cat_id] += $children->f('duration');
		}
		*/
		
		$v = $category_totals[1];
		unset($category_totals[1]);
		arsort($category_totals);
		$category_totals['1'] = $v;
		$position = array_flip(array_keys($category_totals));
		$i=0;
		$j = 0;
		foreach ($category_totals as $category_id => $cat_total){
			if($i > 15){
        	break;
        }	
	    $time = $cat_total; 
		
		if($time < 0){
			continue;
		}	
		if($time)
			$proc = $time * 100 / $total;
		else
			$proc = 0;
		$tags = $assigns[$category_id];
			$tags['PERCENT']  = number_format($proc,2,',','.');
			$tags['TIME_H'] = intval(intval($time) / 3600);
			$tags['TIME_M'] = (intval($time) / 60) % 60;
			$tags['TIME_S'] = intval($time) % 60;
			
			$ft->assign($tags);
			$i++;
			$ft->parse('CATEGORYACTIVITY_ROW_OUT','.categoryactivity_row');
		}				

		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Activity Categories').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		

		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 

			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function categoryactivity_validate(&$ld)                      *
	****************************************************************/
	function categoryactivity_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function softwareinventory(&$ld)                              *
	****************************************************************/
	function softwareinventory(&$ld)
	{
		$this->dbu = new mysql_db();
		global $glob;
		if(!$this->softwareinventory_validate($ld))
		{
			return false;
		}
		
		$fts = new ft(ADMIN_PATH.MODULE.'reports/');
		$fts->define(array('main'=>'softwareinventory.csv'));
		$fts->define_dynamic('template_row','main');

		$l_r = 20000;
		$offset=0;
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);

		$fts->assign(array(
			'TITLE' => 'Software Inventory',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));

		$filter = '';

		$pieces = explode('-',$_SESSION['filters']['f']);
		$filterCount = count($pieces);

		list($department_id,$computer_id,$member_id) = $pieces;

		$department_id = substr($department_id,1);

		if($_SESSION['filters']['t'] == 'users'){
			$member_id = $computer_id;		
		}

		if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
			$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];



		if($filterCount == 1){
			
			$nodeInfo = $this->dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$department_id);
			
			$nodes = array();
			
			$query = $this->dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']);
			
			while ($query->next())
			{
				array_push($nodes,$query->f('department_id'));					
			}
			
			$member_list = array();
			
			switch ($_SESSION[ACCESS_LEVEL])
			{
				case MANAGER_LEVEL:
				// case LIMITED_LEVEL:
				case DPO_LEVEL:
					
					$members = $this->dbu->query("SELECT member_id FROM member2manage WHERE manager_id = ?",$_SESSION[U_ID]);
					
					while ($members->next())
					{
						array_push($member_list,$members->f('member_id'));
					}
						
					break;
					
				case EMPLOYEE_LEVEL:
					$member_list = array($_SESSION[U_ID]);
					
					break;
			}
			switch ($glob['t'])
			{
				case 'session':
				case 'users':
					
					$filter = ' AND member.department_id IN ('.join(',',$nodes).')';	
					break;
				case 'computers':
					
					$filter = ' AND computer.department_id IN ('.join(',',$nodes).')';
					break;
			}
			if(!empty($member_list))
			{
				$filter .= ' AND member.member_id IN ('.join(',',$member_list).')';	
			}
		}else{
			
			switch ($glob['t']){
				case 'session':
					$filter = ' AND member.member_id = '.$member_id.' 
								AND inventory.computer_id = '.$computer_id.'
								AND member.department_id = '.$department_id;
					
					break;
					
				case 'users':
								
					$filter = ' AND member.member_id = '.$member_id.' 
								AND member.department_id = '.$department_id;
					
					break;
					
				case 'computers':

					$filter = ' AND inventory.computer_id = '.$computer_id.' 
								AND computer.department_id = '.$department_id;
					
					break;
			}
		}

		$_SESSION['filters']['t'] = $_SESSION['filters']['t'];
		$_SESSION['filters']['f'] = $_SESSION['filters']['f'];
				


		$this->dbu->query("SELECT 
		member.logon,
		member.alias,
		CONCAT(member.first_name,' ',member.last_name) as member_name,
		member.member_id,
		computer.name,
		computer.ip,
		inventory.last_updated,
		inventory.comptype,
		inventory.os,
		inventory.cpu,
		inventory.ram,
		inventory.mboard,
		inventory.mboardmodel,
		inventory.hdd,
		inventory.hddsize,
		inventory.video,
		inventory.videosize,
		inventory.software,
		inventory.monitor
		FROM inventory 
		INNER JOIN computer ON computer.computer_id = inventory.computer_id
		INNER JOIN member ON member.member_id = inventory.member_id
		WHERE 1=1 ".$filter." ".$number_of_rows);

		/*$max_rows=$this->dbu->records_count();
		$this->dbu->move_to($offset*$l_r);*/
		$i = 0;
		$prev = 0;
		while ($this->dbu->move_next() /*&& $i<$l_r*/){	
			
			if(!$prev)
			{
				$prev= $this->dbu->f('member_id');
				$fts->assign('TOP_BORDER','');
			}
			else
			{
				
				if ($this->dbu->f('member_id') != $prev)
				{
					$prev = $this->dbu->f('member_id');
					$fts->assign('TOP_BORDER','top_border');
				}
				else 
				{
					$fts->assign('TOP_BORDER','');
				}	
			}
	    $soft = unserialize($this->dbu->f('software'));
	    if (is_array($soft)){
		$appname = array();
		foreach ($soft as $key => $row)
		{
			$rowname = base64_decode($row['name']);
			$rowpublisher = base64_decode($row['publisher']);
			$rowinstall = base64_decode($row['install']);
			$appname[$rowname]['name'] = $rowname;
			$appname[$rowname]['publisher'] = $rowpublisher;
			$appname[$rowname]['install'] = $rowinstall;
		}
		sort($appname);
		$apptable = '';
		foreach($appname as $applicationrow) {
			$applicationrowname = $applicationrow['name'];
			$applicationrowpublisher = $applicationrow['publisher'];
			$applicationrowinstall = $applicationrow['install'];
					$apptable .= '"'.$applicationrowname.'","'.$applicationrowpublisher.'","'.(is_numeric($applicationrowinstall)?date("Y-m-d",strtotime($applicationrowinstall)):$applicationrowinstall).'"
						';
				}
	    }
	    $fts->assign(array(
		'USER' => trialEncrypt($this->dbu->f('alias') == 1 ? $this->dbu->f('member_name') : $this->dbu->f('logon')),
		'IP' => trialEncrypt($this->dbu->f('ip'),'ip'),
		'COMPUTER' => trialEncrypt($this->dbu->f('name'),'comp'),
		'LAST_UPDATE' => date('Y-m-d H:i:s',$this->dbu->f('last_updated')),
		'SOFTWARE_INFO' => $apptable,
		'USER_TYPE' => $this->dbu->f('comptype'),
		'USER_OS' => $this->dbu->f('os'),
		'USER_CPU' => $this->dbu->f('cpu'),
		'USER_RAM' => $this->dbu->f('ram'),
		'USER_MB' => $this->dbu->f('mboard') . ' - ' . $this->dbu->f('mboardmodel'),
		'USER_HDD' => $this->dbu->f('hdd') . ' - ' . $this->dbu->f('hddsize'),
		'USER_VIDEO' => $this->dbu->f('video') . ' - ' . $this->dbu->f('videosize'),
		'USER_MONITOR' => $this->dbu->f('monitor'),
	    ));
			
			if(($i % 2)==0 )
			{
				$fts->assign('CLASS','even');
			}
			else
			{
				$fts->assign('CLASS','');
			}
			$fts->parse('TEMPLATE_ROW_OUT','.template_row');
			$i++;
		}

		$start = $offset;
		$end = ceil($max_rows/$l_r);
		$link = '';
		if($end<=5){
			//if there are less then 5 pages then we go about building a normal pagination
			for ($i = 0; $i < $end; $i++){
				$page = $i+1;	
				$class = $page == $start+1 ? 'class="current"' : '';
				$link .= <<<HTML
				<li {$class}><a href="index.php?pag={$glob['pag']}&offset={$i}{$arguments}">{$page}</a></li>
HTML;
			}
		}else{
			if($start == 0 || $start <3){
				for ($i = 0; $i < 5; $i++){
					$page = $i+1;	
					$class = $page == $start+1 ? 'class="current"' : '';
					$link .= <<<HTML
					<li><a href="index.php?pag={$glob['pag']}&offset={$i}" {$class}>{$page}</a></li>
HTML;
				}
			}elseif ($start+2 >= $end-1){
				//we are close to the end
				for ($i = $end-5; $i < $end; $i++){
					$page = $i+1;	
					$class = $page == $start+1 ? 'class="current"' : '';
					$link .= <<<HTML
					<li><a href="index.php?pag={$glob['pag']}&offset={$i}" {$class}>{$page}</a></li>
HTML;
				}
			}else{
				for ($i = $start-2; $i < $start; $i++){
					$page = $i+1;	
					$link .= <<<HTML
					<li><a href="index.php?pag={$glob['pag']}&offset={$i}">{$page}</a></li>
HTML;
				}
				$page = $start+1;
				$class = $page == $start+1 ? 'class="current"' : '';
				$link .= <<<HTML
				<li><a href="index.php?pag={$glob['pag']}&offset={$start}" {$class}>{$page}</a></li>
HTML;
				for ($i = $start+1; $i < $start+3; $i++){
					$page = $i+1;	
					$link .= <<<HTML
					<li><a href="index.php?pag={$glob['pag']}&offset={$i}">{$page}</a></li>
HTML;
				}
			}
		}
		$fts->assign(array(
			'PAGG' => $link,
		));

		if($offset > 0)
		{
			 $fts->assign('BACKLINK',"index.php?pag=".$glob['pag']."&offset=".($offset-1).$arguments);
		}
		else
		{
			 $fts->assign('BACKLINK','#'); 
		}
		if($offset < $end-1)
		{
			 $fts->assign('NEXTLINK',"index.php?pag=".$glob['pag']."&offset=".($offset+1).$arguments);
		}
		else
		{
			 $fts->assign('NEXTLINK','#');
		}
		$fts->assign('LAST_LINK',"index.php?pag=".$glob['pag']."&offset=".($end-1).$arguments);

		if(!$this->dbu->records_count())
		{
			$fts->assign(array(
				'NO_DATA_MESSAGE' => get_pdf_error($fts->lookup('No data to display for your current filters')),
				'DISPLAY'	=> 'none',
			));
		}
		$output_file = $fts->lookup('Software Inventory').'.csv';
		$fts->parse('CONTENT','main');
		
		$file_contents = $fts->fetch('CONTENT');
		unset($fts);
	
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); ; 
					}

			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 

			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function softwareinventory_validate(&$ld)                     *
	****************************************************************/
	function softwareinventory_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function softwareupdates(&$ld)                                *
	****************************************************************/
	function softwareupdates(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->softwareupdates_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'softwareupdates.csv'));
		$ft->define_dynamic('updates_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Software Updates',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filter = '';

		$pieces = explode('-',$_SESSION['filters']['f']);
		$filterCount = count($pieces);
		
		list($department_id,$computer_id,$member_id) = $pieces;
		
		$department_id = substr($department_id,1);
		
		if($_SESSION['filters']['t'] == 'users'){
			$member_id = $computer_id;		
		}
		
		if($filterCount == 1){
			
			$nodeInfo = $this->dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$department_id);
			
			$nodes = array();
			
			$query = $this->dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']);
			
			while ($query->next())
			{
				array_push($nodes,$query->f('department_id'));					
			}
			
			$member_list = array();
			
			switch ($_SESSION[ACCESS_LEVEL])
			{
				case MANAGER_LEVEL:
				//case LIMITED_LEVEL:
				case DPO_LEVEL:
					
					$members = $this->dbu->query("SELECT member_id FROM member2manage WHERE manager_id = ?",$_SESSION[U_ID]);
					
					while ($members->next())
					{
						array_push($member_list,$members->f('member_id'));
					}
						
					break;
					
				case EMPLOYEE_LEVEL:
					$member_list = array($_SESSION[U_ID]);
					
					break;
			}
			switch ($_SESSION['filters']['t'])
			{
				case 'session':
				case 'users':
					
					$filter = ' AND member.department_id IN ('.join(',',$nodes).')';	
					break;
				case 'computers':
					
					$filter = ' AND computer.department_id IN ('.join(',',$nodes).')';
					break;
			}
			if(!empty($member_list))
			{
				$filter .= ' AND member.member_id IN ('.join(',',$member_list).')';	
			}
		}else{
			
			switch ($_SESSION['filters']['t']){
				case 'session':
					$filter = ' AND member.member_id = '.$member_id.' 
								AND application_inventory.computer_id = '.$computer_id.'
								AND member.department_id = '.$department_id;
					
					break;
					
				case 'users':
								
					$filter = ' AND member.member_id = '.$member_id.' 
								AND member.department_id = '.$department_id;
					
					break;
					
				case 'computers':
		
					$filter = ' AND application_inventory.computer_id = '.$computer_id.' 
								AND computer.department_id = '.$department_id;
					
					break;
			}
		}
		
		if(!empty($_SESSION['filters']['time']))
		{
			
			$matches = array();
			preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$_SESSION['filters']['time']['time'],$matches);
			$pieces = array_shift($matches);
			$days = array();
			switch (count($pieces)){
				case 1:
					//echo current($pieces);
					$time = strtotime(current($pieces));
						
					$start_time = mktime(0,0,0,date('n',$time),date('d',$time),date('Y',$time));
					$end_time = mktime(23,59,59,date('n',$time),date('d',$time),date('Y',$time));
					$days = array(date('w',$time));	
					$filter .= ' AND arrival_date BETWEEN '.$start_time.' AND '.$end_time;
					
					break;
					
				case 2:
					$start_time = strtotime(reset($pieces));
					$start_hour = date('G',$start_time);
					$start_time = mktime(0,0,0,date('n',$start_time),date('d',$start_time),date('Y',$start_time));
					//---
					$end_time = strtotime(end($pieces));
					$end_hour = date('G',$end_time);
					$end_time = mktime(0,0,0,date('n',$end_time),date('d',$end_time),date('Y',$end_time));
					//interval see how manny days we have here
					//if more then 7 then we have all the days
					if($end_time-$start_time >= (86400*7)){
						$days = array(0,1,2,3,4,5,6);//all the days
					}else{
						//check which one is bigger
						$start = $sday = date('w',$start_time);
						$end = $eday = date('w',$end_time);
						//if the last day is smaller then the first day then go backwards
						if($sday >= $eday){
							$start = $eday;
							$end = $sday;
						}
						for ($i = $start; $i <= $end;$i++){
							$days[] = $i;
						}
					}
					$filter .= ' AND ( arrival_date >= '.$start_time.' AND arrival_date <= '.$end_time.')';
					
					break;
			}
			
			switch ($_SESSION['filters']['time']['type']){			
				case 2://specific time
					$filter.= ' AND (hour BETWEEN '.$start_hour.' AND '.$end_hour.')';
					break;
				case 3://work time
					//for worktime we can haz some interesting query
					$worktimes = get_workschedule($department_id,$days, 1);
					$time_filter = '';
					foreach ($worktimes as $day => $hours){
						$time_filter .='(application_inventory.day = '.$day.' AND hour >= '.$hours['start_hour'].' AND hour < '.$hours['end_hour'].') OR ';
					}
					$time_filter = rtrim($time_filter,' OR ');
					$filter .= ' AND ('.$time_filter.')';
					break;
				case 4://overtime
					$worktimes = get_workschedule($department_id,$days, 1);
					$time_filter = '';
					foreach ($worktimes as $day => $hours){
						$time_filter .='(application_inventory.day = '.$day.' AND NOT(hour >= '.$hours['start_hour'].' AND hour < '.$hours['end_hour'].')) OR ';
					}
					$time_filter = rtrim($time_filter,' OR ');
					$filter .= ' AND ('.$time_filter.')';
					break;
					break;
				case 1://show all/default
				default:
					break;
			}
		}
		
		$updates = $this->dbu->query("SELECT 
			member.logon,
			member.member_id,
			member.alias,
			CONCAT(member.first_name,' ',member.last_name) AS member_name,
			computer.name,
			computer.ip,
			application.description,
			application_version.version,
			application_path.path,
			application_inventory.arrival_date
			FROM application_inventory 
			INNER JOIN application ON application.application_id = application_inventory.application_id
			INNER JOIN computer ON computer.computer_id = application_inventory.computer_id
			INNER JOIN member ON member.member_id = application_inventory.member_id
			INNER JOIN application_version ON application_inventory.application_version_id = application_version.application_version_id
			INNER JOIN application_path ON application_path.application_path_id = application_inventory.application_path_id
			WHERE 1=1 ".$filter."
			ORDER BY member.logon, application.description ASC");

		$i = 0;
		while ($updates->next())
		{	
			$ft->assign(array(
				'USER' => $updates->f('alias') == 1 ? decode_numericentity($updates->f('member_name')) : decode_numericentity($updates->f('logon')),
				'DATE' => date('m/d/Y h:i:s A', $updates->f('arrival_date')),
				'COMPUTER' => decode_numericentity($updates->f('name'))." (".$updates->f('ip').")",
				'SOFTWARE' => str_replace('||','\\',$updates->f('description')),
			));
			$ft->parse('UPDATES_ROW_OUT','.updates_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Software Updates').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
	

		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}

			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 

			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function softwareupdates_validate(&$ld)                       *
	****************************************************************/
	function softwareupdates_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function topproductive(&$ld)                                  *
	****************************************************************/
	function topproductive(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->topproductive_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'topproductive.csv'));
		$ft->define_dynamic('topprod_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true,true);
		extract($filters,EXTR_OVERWRITE);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Top Productive',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$users_total = $this->dbu->query("SELECT SUM(session_application.duration) AS total_time,
									session.member_id
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						WHERE session_application.time_type = 0 AND 1=1
						".$app_filter."
						GROUP BY session.member_id");
		
		$total = array();
		$total_time = 0;
		while ($users_total->next()){
			$total[$users_total->f('member_id')] = $users_total->f('total_time');
			$total_time += $users_total->f('total_time');
		}
		
		/*$apps =  $this->dbu->query("SELECT application.application_id,application.description, application_productivity.department_id, COALESCE(application_productivity.productive,1) AS productive FROM application 
			LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
			AND application_productivity.link_type = 0
			WHERE application.application_type = 3");*/
			
		$session_website = get_session_website_table();
		$productivity = $this->dbu->query("SELECT SUM(session_application.duration) AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						application.application_type AS type_id, 
						application.application_id                               AS application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.alias,
						member.active
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id 
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = 1 
														  AND application_productivity.link_id = application.application_id 
													      AND application_productivity.link_type < 3
						WHERE session_application.duration > 0
						AND session_application.time_type = 0
						AND      application.application_type != 3
						AND (productive = 2 OR productive = 3)
						".$app_filter."
						GROUP BY member.member_id, application_id
	union 
			 SELECT     sum(session_" . $session_website . ".duration)               AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						3 AS type_id, 
						domain.domain_id                                AS application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.alias,
						member.active
						FROM session_" . $session_website . "
						INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id 
						INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = 1 
														  AND application_productivity.link_id = domain.domain_id 
													      AND application_productivity.link_type = 3
						WHERE session_" . $session_website . ".duration > 0
						AND session_" . $session_website . ".time_type = 0
						AND (productive = 2 OR productive = 3)
                    ".$app_filter."
						GROUP BY member.member_id, application_id");
		
		$data = array();
		$durations = array();
		while ($productivity->next()){
			$duration = 0;
			if(!is_array($data[$productivity->f('member_id')])){
				$data[$productivity->f('member_id')] = array();
			} 
			$duration = $productivity->f('app_duration');
			$data[$productivity->f('member_id')]['name'] = $productivity->f('alias') == 1 ? $productivity->f('first_name').' '.$productivity->f('last_name') : $productivity->f('logon');
			$data[$productivity->f('member_id')]['duration'] += $duration;
			
			$durations[$productivity->f('member_id')] = $data[$productivity->f('member_id')]['duration'];
		}
		
		arsort($durations);
		$i = 0;
		
		foreach ($durations as $member_id => $duration){
			$tags = $data[$member_id];
			$proc = ($tags['duration'] * 100 / $total[$member_id]);
			$ft->assign(array(
				'USERNAME' => trialEncrypt(decode_numericentity($tags['name'])),
				'PROCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($total[$member_id]) / 3600),
				'TOTAL_TIME_M' => (intval($total[$member_id]) / 60) % 60,
				'TOTAL_TIME_S' => intval($total[$member_id]) % 60,
				'PROD_TIME_H' => intval(intval($tags['duration']) / 3600),
				'PROD_TIME_M' => (intval($tags['duration']) / 60) % 60,
				'PROD_TIME_S' => intval($tags['duration']) % 60,
			));
			$ft->parse('TOPPROD_ROW_OUT','.topprod_row');
			$i++;
		}

		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Top Productive').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function topproductive_validate(&$ld)                         *
	****************************************************************/
	function topproductive_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function topunproductive(&$ld)                                *
	****************************************************************/
	function topunproductive(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->topunproductive_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'topunproductive.csv'));
		$ft->define_dynamic('topunprod_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true,true);
		extract($filters,EXTR_OVERWRITE);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Top Unproductive',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$users_total = $this->dbu->query("SELECT SUM(session_application.duration) AS total_time,
									session.member_id
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						WHERE session_application.time_type = 0 AND 1=1
						".$app_filter."
						GROUP BY session.member_id");

		
		$total = array();
		$total_time = 0;
		while ($users_total->next()){
			$total[$users_total->f('member_id')] = $users_total->f('total_time');
			$total_time += $users_total->f('total_time');
		}
		
		$apps =  $this->dbu->query("SELECT application.application_id,application.description, application_productivity.department_id, COALESCE(application_productivity.productive,1) AS productive FROM application 
			LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
			AND application_productivity.link_type = 0
			WHERE application.application_type = 3");


		$apps_productivity_id = array();
		while ($apps->next()){
			if(!array_key_exists($apps->f('department_id'),$apps_productivity_id))
				$apps_productivity_id[$apps->f('department_id')]=($apps->f('application_productivity_id')?$apps->f('application_productivity_id'):0);
		}
		
		$productivity = $this->dbu->query("SELECT SUM(session_application.duration) AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						application.application_type AS type_id, 
						application.application_id,
						member.logon,						
						member.member_id,
						member.alias,
						CONCAT(member.first_name,' ',member.last_name) AS member_name,
						casualty.cost_per_hour,
						casualty.currency
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id 
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
														  AND application_productivity.link_id = application.application_id 
													      AND application_productivity.link_type = 0
						LEFT JOIN casualty ON member.department_id = casualty.department_id
						WHERE session_application.duration > 0
						AND session_application.time_type = 0
						AND (productive = 0 OR productive = 3)
						".$app_filter."
						GROUP BY member.member_id,session_application.application_id
						ORDER BY app_duration DESC");
		
		$data = array();
		$durations = array();
		while ($productivity->next()){
			$duration = 0;
			if(!is_array($data[$productivity->f('member_id')])){
				$data[$productivity->f('member_id')] = array();
			} 
			$duration = $productivity->f('app_duration');
			if($productivity->f('productive') == 3){
				
				switch ($productivity->f('type_id')){
			case 1:
		    // 1 chat
		    	$session_table = 'chat';
			    $table = 'chat';
			    $primary = $table.'_id';
		    	break;
		    case 2:
		    // 2 document
		    	$session_table = 'document';
		    	$table = 'document';
			    $primary = $table.'_id';
		    	break;
		    case 3:
		    // 3 site 
		    	$session_table = 'website';
		    	$table = 'domain';
			    $primary = $table.'_id';
		    	break;
				}
				$duration = 0;
				$children = $this->dbu->query("SELECT SUM(session_".$session_table.".duration) AS app_duration,
	    			COALESCE(application_productivity.productive,1) AS productive,
					application_productivity.link_id as app_link_id,
					member.department_id as member_department_id
	    			FROM session_".$session_table."
	    			INNER JOIN ".$table." ON ".$table.".".$primary." = session_".$session_table.".".$primary."
					INNER JOIN session ON session.session_id = session_".$session_table.".session_id
					".$app_join."
					 INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
							AND application_productivity.link_id = ".$table.".".$primary."
							AND application_productivity.link_type = '".$productivity->f('type_id')."'
					WHERE ".(($productivity->f('type_id') == 3)?"":"session_".$session_table.".time_type = 0 AND ")."2=2 AND session_".$session_table.".application_id = '".$productivity->f('application_id')."'
					AND member.member_id = ".$productivity->f('member_id')."
					AND application_productivity.productive = 0
					".$app_filter."
					GROUP BY application_productivity.productive");

		while ($children->next()){
			$division_number = get_division_number($children->f('app_link_id'),$children->f('member_department_id'));
			if ($productivity->f('type_id') == 3){$tempduration = ($children->f('app_duration') / $division_number);} else {$tempduration = $children->f('app_duration');}
			$duration += $tempduration;
		}
			}
			$data[$productivity->f('member_id')]['name'] = $productivity->f('alias') == 1 ? $productivity->f('member_name') : $productivity->f('logon');
			$data[$productivity->f('member_id')]['duration'] += $duration;
			
			$data[$productivity->f('member_id')]['cost'] = $productivity->f('cost_per_hour');
			$data[$productivity->f('member_id')]['currency'] = $productivity->f('currency');
			
			$durations[$productivity->f('member_id')] = $data[$productivity->f('member_id')]['duration'];
		}
		
		arsort($durations);
		$i = 0;
		
		/*$pieces = split('-',$_SESSION['filters']['f']);
		$department_id = substr($pieces[0],1);

		$cost = $this->dbu->row("SELECT cost_per_hour, currency FROM casualty WHERE department_id='".$department_id."'");*/
		
		foreach ($durations as $member_id => $duration){
			$tags = $data[$member_id];
			$proc = ($tags['duration'] * 100 / $total[$member_id]);
			$ft->assign(array(
				'USERNAME' => trialEncrypt(decode_numericentity($tags['name'])),
				'PROCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($total[$member_id]) / 3600),
				'TOTAL_TIME_M' => (intval($total[$member_id]) / 60) % 60,
				'TOTAL_TIME_S' => intval($total[$member_id]) % 60,				
				'UNPROD_TIME_H' => intval(intval($tags['duration']) / 3600),
				'UNPROD_TIME_M' => (intval($tags['duration']) / 60) % 60,
				'UNPROD_TIME_S' => intval($tags['duration']) % 60,
				'COST' => number_format($data[$member_id]['cost'] * ($tags['duration'] / 3600),2),
				'CURRENCY' => get_currency($data[$member_id]['currency']),
			));
			$ft->parse('TOPUNPROD_ROW_OUT','.topunprod_row');
			$i++;
		}

		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Top Unproductive').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function topunproductive_validate(&$ld)                       *
	****************************************************************/
	function topunproductive_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function topactive(&$ld)                                      *
	****************************************************************/
	function topactive(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->topactive_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'topactive.csv'));
		$ft->define_dynamic('topactive_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true,true);
		extract($filters,EXTR_OVERWRITE);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Top Active',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$users_total = $this->dbu->query("SELECT SUM(session_activity.duration) AS active_time,member.member_id FROM session_activity 
			INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
			WHERE 1=1  AND session_activity.activity_type < 2 ".$app_filter." GROUP by session.member_id");
		
		$total = array();
		$total_time = 0;
		while ($users_total->next()){
			$total[$users_total->f('member_id')] = $users_total->f('active_time');
			$total_time += $users_total->f('active_time');
		}
		
		$active = $this->dbu->query("SELECT SUM(session_activity.duration) AS active_time,member.member_id,member.logon,
		member.alias,
		CONCAT(member.first_name,' ',member.last_name) AS member_name FROM session_activity 
			INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
			WHERE 1=1 ".$app_filter." AND session_activity.activity_type = 1 GROUP by session.member_id ORDER BY active_time DESC");
		
		$i = 0;

		while ($active->next()){	
			$proc = ($active->f('active_time') * 100 / $total[$active->f('member_id')]);
			$ft->assign(array(
				'USERNAME' => trialEncrypt($active->f('alias') == 1 ? decode_numericentity($active->f('member_name')) : decode_numericentity($active->f('logon'))),
				'PROCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($total[$active->f('member_id')]) / 3600),
				'TOTAL_TIME_M' => (intval($total[$active->f('member_id')]) / 60) % 60,
				'TOTAL_TIME_S' => intval($total[$active->f('member_id')]) % 60,				
				'ACTIVE_TIME_H' => intval(intval($active->f('active_time')) / 3600),
				'ACTIVE_TIME_M' => (intval($active->f('active_time')) / 60) % 60,
				'ACTIVE_TIME_S' => intval($active->f('active_time')) % 60,
			));
			$ft->parse('TOPACTIVE_ROW_OUT','.topactive_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Top Active').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }	
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function topactive_validate(&$ld)                             *
	****************************************************************/
	function topactive_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}

	/****************************************************************
	* function topidle(&$ld)                                        *
	****************************************************************/
	function topidle(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->topidle_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'topidle.csv'));
		$ft->define_dynamic('topidle_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true,true);
		extract($filters,EXTR_OVERWRITE);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Top Idle',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$users_total = $this->dbu->query("SELECT SUM(session_activity.duration) AS idle_time,member.member_id FROM session_activity 
			INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
			WHERE 1 = 1 AND session_activity.activity_type < 2 ".$app_filter." GROUP by session.member_id");
		
		$total = array();
		$total_time = 0;
		while ($users_total->next()){
			$total[$users_total->f('member_id')] = $users_total->f('idle_time');
			$total_time += $users_total->f('idle_time');
		}
		
		$idle = $this->dbu->query("SELECT SUM(session_activity.duration) AS idle_time, member.member_id, member.logon,
			member.alias,
			CONCAT(member.first_name,' ',member.last_name) AS member_name FROM session_activity 
			INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
			WHERE 1=1 ".$app_filter." AND session_activity.activity_type = 0 GROUP by session.member_id ORDER BY idle_time DESC");
		
		$i = 0;

		while ($idle->next()){	
			$proc = ($idle->f('idle_time') * 100 / $total[$idle->f('member_id')]);
			$ft->assign(array(
				'USERNAME' => trialEncrypt($idle->f('alias') == 1 ? decode_numericentity($idle->f('member_name')) : decode_numericentity($idle->f('logon'))),
				'PROCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($total[$idle->f('member_id')]) / 3600),
				'TOTAL_TIME_M' => (intval($total[$idle->f('member_id')]) / 60) % 60,
				'TOTAL_TIME_S' => intval($total[$idle->f('member_id')]) % 60,				
				'IDLE_TIME_H' => intval(intval($idle->f('idle_time')) / 3600),
				'IDLE_TIME_M' => (intval($idle->f('idle_time')) / 60) % 60,
				'IDLE_TIME_S' => intval($idle->f('idle_time')) % 60,
			));
			$ft->parse('TOPIDLE_ROW_OUT','.topidle_row');
			$i++;
		}

		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Top Idle').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function topidle_validate(&$ld)                               *
	****************************************************************/
	function topidle_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function toponline(&$ld)                                      *
	****************************************************************/
	function toponline(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->toponline_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'toponline.csv'));
		$ft->define_dynamic('toponline_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true,true);
		extract($filters,EXTR_OVERWRITE);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Top Online',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$users_total = $this->dbu->query("SELECT SUM(session_activity.duration) AS active_time,
									member.member_id 
									FROM session_activity 
									INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
									WHERE 1=1 AND session_activity.activity_type = 1 ".$app_filter." GROUP by session.member_id");
		
		$total = array();
		$total_time = 0;
		while ($users_total->next()){
			$total[$users_total->f('member_id')] = $users_total->f('active_time');
			$total_time += $users_total->f('active_time');
		}
		
		$online = $this->dbu->query("SELECT SUM(session_application.duration) as app_duration,member.logon,member.member_id,
			member.alias,
			CONCAT(member.first_name,' ',member.last_name) AS member_name
			FROM session_application
			INNER JOIN session ON session.session_id = session_application.session_id
			INNER JOIN application ON application.application_id = session_application.application_id
			".$app_join."
			WHERE 1=1 AND session_application.time_type = 0 AND application.application_type  IN (".ONLINE_TIME_INCLUDE.")".$app_filter."
			GROUP BY member.member_id
			ORDER BY app_duration DESC");
		
		$i = 0;

		while ($online->next()){	
			$proc = ($online->f('app_duration') * 100 / $total[$online->f('member_id')]);
			$ft->assign(array(
				'USERNAME' => trialEncrypt($online->f('alias') == 1 ? decode_numericentity($online->f('member_name')) : decode_numericentity($online->f('logon'))),
				'PROCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($total[$online->f('member_id')]) / 3600),
				'TOTAL_TIME_M' => (intval($total[$online->f('member_id')]) / 60) % 60,
				'TOTAL_TIME_S' => intval($total[$online->f('member_id')]) % 60,				
				'ONLINE_TIME_H' => intval(intval($online->f('app_duration')) / 3600),
				'ONLINE_TIME_M' => (intval($online->f('app_duration')) / 60) % 60,
				'ONLINE_TIME_S' => intval($online->f('app_duration')) % 60,
			));
			$ft->parse('TOPONLINE_ROW_OUT','.toponline_row');
			$i++;
		}

		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Top Online').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir');
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function toponline_validate(&$ld)                             *
	****************************************************************/
	function toponline_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function topwebsites(&$ld)                                   *
	****************************************************************/
	function topwebsites(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->topwebsites_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'topwebsites.csv'));
		$ft->define_dynamic('topwebsites_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Top Websites',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$session = $this->dbu->row("SELECT SUM(session_website.duration) AS duration FROM session_website 
			INNER JOIN session ON session.session_id = session_website.session_id
			INNER JOIN domain ON session_website.domain_id = domain.domain_id
			".$app_join." WHERE 1=1
			AND session_website.time_type = 0
			". $app_filter);
		
		$total = $session['duration'];
		
		$website = $this->dbu->query("SELECT SUM(session_website.duration) as website_duration,
			domain.domain,
			session_website.domain_id
			FROM session_website
			INNER JOIN domain ON domain.domain_id = session_website.domain_id
			INNER JOIN session ON session.session_id = session_website.session_id
			".$app_join."
			WHERE 1=1 AND session_website.time_type = 0 ".$app_filter."
			AND session_website.duration > 0
			GROUP BY session_website.domain_id
			ORDER BY website_duration desc");
		
		$i = 0;
		$tot = 0;
		while ($website->next()){
			$proc = ($website->f('website_duration') * 100 / $total);
				
			$this->dbu->query("SELECT SUM(session_website.duration) as website_duration, member.logon,
				member.alias,
				CONCAT(member.first_name,' ',member.last_name) AS member_name FROM session_website 
				INNER JOIN session ON session.session_id = session_website.session_id
				INNER JOIN domain ON session_website.domain_id = domain.domain_id
				".$app_join."
				WHERE session_website.duration > 0 AND session_website.time_type = 0 AND session_website.domain_id = '".$website->f('domain_id')."'
				".$app_filter."
				GROUP BY member.member_id
				ORDER BY website_duration desc");
			
			$user = "";
			while ($this->dbu->move_next()) {
				$logon = $this->dbu->f('alias') == 1 ? $this->dbu->f('member_name'): $this->dbu->f('logon');
				$user .= $logon." - ".format_time($this->dbu->f('website_duration'))."\n";
			}
			
			$user = rtrim($user);
			
			$ft->assign(array(
				'WWW' => $website->f('domain'),
				'USERNAME' => decode_numericentity($user),
				'PROCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($website->f('website_duration')) / 3600),
				'TOTAL_TIME_M' => (intval($website->f('website_duration')) / 60) % 60,
				'TOTAL_TIME_S' => intval($website->f('website_duration')) % 60,	
			));
			$tot += $website->f('website_duration');
			$ft->parse('TOPWEBSITES_ROW_OUT','.topwebsites_row');
			$i++;
		}
		

		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Top Websites').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
					}
			
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function topwebsites_validate(&$ld)                          *
	****************************************************************/
	function topwebsites_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function topapplications(&$ld)                                *
	****************************************************************/
	function topapplications(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->topapplications_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'topapplications.csv'));
		$ft->define_dynamic('topapplications_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Top Applications',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],'all');

		$session = $this->dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."  WHERE 1 = 1 AND session_application.time_type = 0 ". $app_filter);
		
		$total = $session['duration'];
		
		$application = $this->dbu->query("SELECT SUM(session_application.duration) as app_duration,
			application.description as name, 
			session_application.application_id 
			 FROM session_application 
			INNER JOIN application ON application.application_id = session_application.application_id
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."
			WHERE session_application.duration > 0
			AND session_application.time_type = 0 
			".$app_filter."
			GROUP BY session_application.application_id
			ORDER BY app_duration desc");
		
		$i = 0;
		$tot = 0;
		
		while ($application->next()){
			$proc = ($application->f('app_duration') * 100 / $total);
			
			$this->dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name,member.logon,
				member.alias,
				CONCAT(member.first_name,' ',member.last_name) AS member_name
				FROM session_application 
				INNER JOIN application ON application.application_id = session_application.application_id
				INNER JOIN session ON session.session_id = session_application.session_id
				".$app_join."
				WHERE session_application.duration > 0 AND session_application.application_id = '".$application->f('application_id')."'
				AND session_application.time_type = 0 ".$app_filter."
				GROUP BY member.member_id
				ORDER BY app_duration desc");
			
			$user = '';
			while ($this->dbu->move_next()) {
				$logon = $this->dbu->f('alias') == 1 ?  $this->dbu->f('member_name') :  $this->dbu->f('logon');
				$user .= $logon." - ".format_time($this->dbu->f('app_duration'))."\n";
			}
			
			$cat_name = $ft->lookup('Uncategorised');
	
			if(isset($categories[$application->f('application_id').'-0'])){
				$cat_name = $categories[$application->f('application_id').'-0']['category'];
			}
			else if(isset($categories[$application->f('application_id').'-1'])){
					$cat_name = $categories[$application->f('application_id').'-1']['category'];
				}
				else if(isset($categories[$application->f('application_id').'-2'])){
						$cat_name = $categories[$application->f('application_id').'-2']['category'];
					}
					else if(isset($categories[$application->f('application_id').'-3'])){
							$cat_name = $categories[$application->f('application_id').'-3']['category'];
						}
			
			$user = rtrim($user);
			
			$ft->assign(array(
				'APPLICATION' => decode_numericentity($application->f('name')),
				'CATEGORY' => decode_numericentity($cat_name), 
				'USERNAME' => decode_numericentity($user),
				'PROCENT' => number_format($proc,2,',','.'),
				'TOTAL_TIME_H' => intval(intval($application->f('app_duration')) / 3600),
				'TOTAL_TIME_M' => (intval($application->f('app_duration')) / 60) % 60,
				'TOTAL_TIME_S' => intval($application->f('app_duration')) % 60,	
			));
			
			$ft->parse('TOPAPPLICATIONS_ROW_OUT','.topapplications_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Top Applications').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);
					}
			
			else{
				header('Pragma: public');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function topapplications_validate(&$ld)                       *
	****************************************************************/
	function topapplications_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function timeline(&$ld)                                       *
	****************************************************************/
	function timeline(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->timeline_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'timeline.csv'));
		$ft->define_dynamic('template_row','main');
		
		$pieces = explode('-',$_SESSION['filters']['time']['time']);
		if(count($pieces) == 2){
			$start_time = strtotime(reset($pieces));
			$end_time = strtotime(end($pieces));
			if(($end_time - $start_time) > 86400){
				$end_time = $start_time + 86400;
			}
			$_SESSION['filters']['time']['time'] = date('n/d/Y g:i A',$start_time).' - '.date('n/d/Y g:i A',$end_time);
		}
		
		$pieces = explode('-',$_SESSION['filters']['f']);
		$type = substr($pieces[0],0,1);
		$pieces[0] = substr($pieces[0],1);

		if(count($pieces) == 1)
		{
			$department_id = current($pieces);
			$member = $this->dbu->row("SELECT member.department_id,member.member_id,computer.computer_id FROM member 
			INNER JOIN computer2member ON computer2member.member_id = member.member_id
			INNER JOIN computer ON computer.computer_id = computer2member.computer_id
			WHERE computer.department_id = ? AND member.department_id = ?",array($department_id,$department_id));
			
			if(empty($member))
			{
				//no member? just fetch something then..
				$member = $this->dbu->row("SELECT member.department_id,member.member_id,computer.computer_id FROM member 
				INNER JOIN computer2member ON computer2member.member_id = member.member_id
				INNER JOIN computer ON computer.computer_id = computer2member.computer_id
				LIMIT 1");
				$type = 's';
			}
			
			$_SESSION['filters']['f'] = $type.$member['department_id'].'-'.$member['computer_id'].'-'.$member['member_id'];
		}
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time']);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],'all');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Timeline',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
				
		$additional_application_filter = '';
		
		if($ld['app'] && !empty($ld['app']) )
		{
			$additional_application_filter .= " AND application.application_id = ".$ld['app'];
		}
		
		if($ld['win'] && !empty($ld['win']) )
		{
			$additional_application_filter .= " AND window.window_id = ".$ld['win'];
			$additional_application_filter .= " AND session_log.active = ".$ld['wact'];
		}
		
		//add the extra filter for the minutes
		$matches = array();
		preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$_SESSION['filters']['time']['time'],$matches);
		$pieces = array_shift($matches);
		switch (count($pieces)){
			case 1:
				$time = strtotime(current($pieces));
				$app_filter .= ' AND session_log.start_time >= '.$time;
				break;
			case 2:
				$start_time = strtotime(reset($pieces));
				$end_time = strtotime(end($pieces));
				
				$app_filter .= ' AND (session_log.start_time BETWEEN '.$start_time.' AND '.$end_time.')';
				break;
		}
		
		$this->dbu->query("SELECT application.description, 
		application.application_id,
		window.name,
		session_log.* 
		FROM session_log 
		INNER JOIN window ON window.window_id = session_log.window_id
		INNER JOIN application ON application.application_id = session_log.application_id 
		INNER JOIN session ON session.session_id = session_log.session_id 
		".$app_join." 
		WHERE 1=1 ".$app_filter.$additional_application_filter . " ORDER BY start_time ASC");

		$data = array_fill(0,24,array('tags'=> array(),'total' => 0,'private_end'=> array(),'private_start'=> array(),'private_total' => 0));
		$i = 0;
		
		while ($this->dbu->move_next()){
			
			$type = '';
			switch ($this->dbu->f('type_id')){
				case 1:
					$type = 'chats';
					break;
				case 2:
					$type = 'documents';
					break;
				case 3:
					$type = 'sites';
					break;
				default:
					$type = 'windows';	
			}
			
			if($this->dbu->f('duration') == 0){
				continue;
			}
			
			if(!isset($data[$this->dbu->f('hour')])){
				$data[$this->dbu->f('hour')] = array();
			}
			$index = count($data[$this->dbu->f('hour')]['tags']);
			
			if($index != 0)
			{
				
				$index--;
				
				if($data[$this->dbu->f('hour')]['tags'][$index]['APP_ID']  == $this->dbu->f('application_id') && in_array($this->dbu->f('active'), $data[$this->dbu->f('hour')]['tags'][$index]['ACTIVITY_TYPE']))
				{
					$data[$this->dbu->f('hour')]['tags'][$index]['TIME'] += $this->dbu->f('duration');
					$data[$this->dbu->f('hour')]['tags'][$index]['INTERVAL_END'] = date('g:i:s A',$this->dbu->f('end_time'));
					$data[$this->dbu->f('hour')]['tags'][$index]['TIME_FORMATED'] = format_time($data[$this->dbu->f('hour')]['tags'][$index]['TIME']);
					$str = $data[$this->dbu->f('hour')]['tags'][$index]['TIMELINE_INFO'];
					$end = $data[$this->dbu->f('hour')]['tags'][$index]['END_TIME'];
					$str = str_replace('end='.$end,'end='.$this->dbu->f('end_time'),$str);
					$data[$this->dbu->f('hour')]['tags'][$index]['TIMELINE_INFO'] = $str;
					$data[$this->dbu->f('hour')]['tags'][$index]['END_TIME'] = $this->dbu->f('end_time');
					
					if(in_array($this->dbu->f('active'),array(2,3)))
					{
						array_push($data[$this->dbu->f('hour')]['private_end'], date('g:i:s A',$this->dbu->f('end_time')));
					}
				}
				else
				{
					$cat_name = $ft->lookup('Uncategorised');
					$cat_id = 1;
					
					if(isset($categories[$this->dbu->f('application_id').'-0'])){
						$cat_name = $ft->lookup($categories[$this->dbu->f('application_id').'-0']['category']);
						$cat_id = $categories[$this->dbu->f('application_id').'-0']['category_id'];
					}
					else if(isset($categories[$this->dbu->f('application_id').'-1'])){
							$cat_name = $ft->lookup($categories[$this->dbu->f('application_id').'-1']['category']);
							$cat_id = $categories[$this->dbu->f('application_id').'-1']['category_id'];
						}
						else if(isset($categories[$this->dbu->f('application_id').'-2'])){
								$cat_name = $ft->lookup($categories[$this->dbu->f('application_id').'-2']['category']);
								$cat_id = $categories[$this->dbu->f('application_id').'-2']['category_id'];
							}
							else if(isset($categories[$this->dbu->f('application_id').'-3'])){
								$cat_name = $ft->lookup($categories[$this->dbu->f('application_id').'-3']['category']);
								$cat_id = $categories[$this->dbu->f('application_id').'-3']['category_id'];
							}
					
					if(in_array($this->dbu->f('active'),array(2,3)))
					{
						$data[$this->dbu->f('hour')]['total_private_app']++;
						
						array_push($data[$this->dbu->f('hour')]['private_start'], date('g:i:s A',$this->dbu->f('start_time')));
						array_push($data[$this->dbu->f('hour')]['private_end'], date('g:i:s A',$this->dbu->f('end_time')));
					}
					
					array_push($data[$this->dbu->f('hour')]['tags'], array(
						'APP_ID' => $this->dbu->f('application_id'),
						'APPLICATION' => decode_numericentity($this->dbu->f('description')),
						'ACTIVITY_TYPE' => $this->dbu->f('active') == 0 || $this->dbu->f('active') == 1 ? array(0,1) : array(2,3),
						'TIME' => $this->dbu->f('duration'),
						'INTERVAL_START' => date('g:i:s A',$this->dbu->f('start_time')),
						'INTERVAL_END' => date('g:i:s A',$this->dbu->f('end_time')),
						'TIME_FORMATED' => format_time($this->dbu->f('duration')),
						'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$this->dbu->f('session_id').'&app='.$this->dbu->f('application_id').'&start='.$this->dbu->f('start_time').'&type='.$this->dbu->f('type_id').'&end='.$this->dbu->f('end_time'),
						'APPLICATION_ID' => $this->dbu->f('application_id'),
						'CATEGORY' => decode_numericentity($cat_name),
						'CATEGORY_ID' => $cat_id,
						'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$this->dbu->f('application_id'),
						'END_TIME' => $this->dbu->f('end_time'),
						'WINDOWS_TYPE' => $type,
						'ID' => $this->dbu->f('application_id'),
						'EXPAND' => array()
						));	
				}
			}
			else
			{
				$cat_name = $ft->lookup('Uncategorised');
				$cat_id = 1;
				
				if(isset($categories[$this->dbu->f('application_id').'-0'])){
					$cat_name = $ft->lookup($categories[$this->dbu->f('application_id').'-0']['category']);
					$cat_id = $categories[$this->dbu->f('application_id').'-0']['category_id'];
				}
				
				if(in_array($this->dbu->f('active'),array(2,3)))
				{
					$data[$this->dbu->f('hour')]['total_private_app']++;
					
					array_push($data[$this->dbu->f('hour')]['private_start'], date('g:i:s A',$this->dbu->f('start_time')));
					array_push($data[$this->dbu->f('hour')]['private_end'], date('g:i:s A',$this->dbu->f('end_time')));
				}
				
				array_push($data[$this->dbu->f('hour')]['tags'], array(
					'APP_ID' => $this->dbu->f('application_id'),
					'APPLICATION' => decode_numericentity($this->dbu->f('description')),
					'TIME' => $this->dbu->f('duration'),
					'ACTIVITY_TYPE' => $this->dbu->f('active') == 0 || $this->dbu->f('active') == 1 ? array(0,1) : array(2,3),
					'INTERVAL_START' => date('g:i:s A',$this->dbu->f('start_time')),
					'INTERVAL_END' => date('g:i:s A',$this->dbu->f('end_time')),
					'TIME_FORMATED' => format_time($this->dbu->f('duration')),
					'WINDOWS_TYPE' => $type,
					'APPLICATION_ID' => $this->dbu->f('application_id'),
					'CATEGORY' => decode_numericentity($cat_name),
					'CATEGORY_ID' => $cat_id,
					'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$this->dbu->f('application_id'),
					'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$this->dbu->f('session_id').'&app='.$this->dbu->f('application_id').'&start='.$this->dbu->f('start_time').'&type='.$this->dbu->f('type_id').'&end='.$this->dbu->f('end_time'),
					'END_TIME' => $this->dbu->f('end_time'),
					'ID' => $this->dbu->f('application_id'),
				));	
			}
			
			$data[$this->dbu->f('hour')]['total'] += $this->dbu->f('duration');
			
			if(in_array($this->dbu->f('active'),array(2,3)))
			{
				$data[$this->dbu->f('hour')]['private_total'] += $this->dbu->f('duration');
			}
			
			$total_duration += $this->dbu->f('duration');
			
			if($this->dbu->f('hour') > 12)
			{
				$data[$this->dbu->f('hour')]['name'] = ($this->dbu->f('hour') - 12).':00 PM';	
			}
			else if ($this->dbu->f('hour') == 12)
			{
				$data[$this->dbu->f('hour')]['name'] = '12:00 PM';	
			}
			else
			{
				$data[$this->dbu->f('hour')]['name'] = $this->dbu->f('hour') .':00 AM';	
			}
		}

		$counter = 0;
		for($i = 0,$len = count($data); $i < $len;$i++){
			if(empty($data[$i]['tags'])){
				continue;
			}
			
			$do_not_parse_private_for_this_hour_anymore = false;
			
			foreach ($data[$i]['tags'] as $key => $value){
				
				
				
				if(in_array(2,$value['ACTIVITY_TYPE']))
				{
					$value['APPLICATION'] = 'Private Time';
					$value['EXPAND'] = array();
					$value['CATEGORY'] = '';
					$value['WINDOWS_TYPE'] = '';
					$value['APP_FILTER_LINK'] = '#';
					$total_windows = 0;
					$value['INTERVAL_START'] = reset($data[$i]['private_start']);
					$value['INTERVAL_END'] = end($data[$i]['private_end']);
					$value['TIME_FORMATED'] = format_time($data[$i]['private_total']);
				}
				
				$ft->assign(array(
					'START' => $value['INTERVAL_START'],
					'END' => $value['INTERVAL_END'],
					'HOUR' => $data[$i]['name'],
					'APPLICATION' => decode_numericentity($value['APPLICATION']),
					'CATEGORY' => decode_numericentity($value['CATEGORY']),
				));
				
				if(in_array(1,$value['ACTIVITY_TYPE']))
				{
					$ft->parse('TEMPLATE_ROW_OUT','.template_row');
					$counter++;
				}
				else if( !$do_not_parse_private_for_this_hour_anymore && in_array(2,$value['ACTIVITY_TYPE']) && !$ld['app'])
				{
					$ft->parse('TEMPLATE_ROW_OUT','.template_row');
					$counter++;
					$do_not_parse_private_for_this_hour_anymore = true;
				}		
			}
		}
		
		if(empty($value)){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Timeline').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}
			
			else{
			header('Pragma: public');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function timeline_validate(&$ld)                              *
	****************************************************************/
	function timeline_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function applicationusageperuser(&$ld)                        *
	****************************************************************/
	function applicationusageperuser(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->applicationusageperuser_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'applicationusageperuser.csv'));
		$ft->define_dynamic('template_row','main');
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],0);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Application Usage / Per User',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$session = $this->dbu->query("SELECT SUM(session_application.duration) AS duration,
		session.session_id,
		member.member_id 
		FROM session_application 
		INNER JOIN session ON session.session_id = session_application.session_id
		".$app_join." 
		WHERE 1=1 
		AND session_application.time_type = 0
		". $app_filter." 
		GROUP BY member.member_id");
		
		$i=0;
		
		$total = array();
				
		while($session->next()){
			$total[$session->f('member_id')] = $session->f('duration');
		}
		
		$application = $this->dbu->query("SELECT SUM(session_application.duration) as app_duration,
		application.description as name, 
		session_application.application_id,
		application_productivity.productive,
		member.logon,
		CONCAT(member.first_name,' ',member.last_name ) AS member_name,
		member.alias,
		member.member_id
		FROM session_application 
		INNER JOIN application ON application.application_id = session_application.application_id
		INNER JOIN session ON session.session_id = session_application.session_id
		".$app_join."
		LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id 
		AND application_productivity.link_id = application.application_id 
		AND application_productivity.link_type = 0
		WHERE session_application.duration > 0
		AND session_application.time_type = 0
		".$app_filter."
		GROUP BY session_application.application_id,member.member_id
		ORDER BY member.member_id, app_duration desc");
		
		$i = 0;
		
		$prev = 0;
		while ($application->next()){
			
			$proc = ($application->f('app_duration') * 100 / $total[$application->f('member_id')]);
			
			$cat_name = $ft->lookup('Uncategorised');
			$cat_id = 1;
			if(isset($categories[$application->f('application_id').'-0'])){
				$cat_name = $categories[$application->f('application_id').'-0']['category'];
				$cat_id = $ft->lookup($categories[$application->f('application_id').'-0']['category_id']);
			}
			
			$ft->assign(array(
				'USER'	=> $application->f('alias') ? decode_numericentity($application->f('member_name')) : decode_numericentity($application->f('logon')),
				'APPLICATION' => decode_numericentity($application->f('name')),
				'PERCENT' => number_format($proc,2,',','.'),
				'TIME_H' => intval(intval($application->f('app_duration')) / 3600),
				'TIME_M' => (intval($application->f('app_duration')) / 60) % 60,
				'TIME_S' => intval($application->f('app_duration')) % 60,
				'CATEGORY' => $cat_name,
			));
			
			$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			$i++;
		}
		
		if(!$i){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Application Usage (Per user)').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		

		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}
			
			else {
			header('Pragma: public');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function applicationusageperuser_validate(&$ld)               *
	****************************************************************/
	function applicationusageperuser_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
	
	/****************************************************************
	* function alerts(&$ld)                                     *
	****************************************************************/
	function triggered(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->alerts_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'triggered.csv'));
		$ft->define_dynamic('ua_row','main');
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Alerts',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
		extract($filters,EXTR_OVERWRITE);

		$triggered =  $this->dbu->query("SELECT alert.name,
									alert.alert_type,
									alert_trigger.*, 
									member.logon,
									member.alias,
									member.first_name,
									member.last_name,
									member.active
									FROM alert_trigger
									INNER JOIN alert ON alert.alert_id = alert_trigger.alert_id
									INNER JOIN session ON session.session_id = alert_trigger.session_id
									INNER JOIN department ON department.department_id = alert_trigger.department_id
									".$app_join."
									WHERE 1=1 ".$alert_filter."
									ORDER BY triggered_date DESC");



		$i=0;
		$opts = array('','Work Schedule Alert','Idle Time Alert','Online Time Alert','Applications Alert','Monitor Alert','Website Alert');
		while($triggered->next()){
			$ft->assign(array(
				'ALERT_NAME' => $triggered->f('name'),
				'ALERT_TYPE' => $ft->lookup($opts[$triggered->f('alert_type')]),
				'MEMBER' => trialEncrypt($triggered->f('alias') == 1 ? decode_numericentity($triggered->f('first_name')).' '.decode_numericentity($triggered->f('last_name')) : decode_numericentity($triggered->f('logon'))),
				'DEPARTMENT' => $triggered->f('department_name'),
				'DATE' => date('d/m/Y h:i A',$triggered->f('triggered_date')),
			));
			//get the rules for this alert
			switch ($triggered->f('alert_type')){
				case 1://work alert;
					$rule = $this->dbu->query("SELECT * FROM alert_time WHERE alert_time_id = ".$triggered->f('rule_id'));
					$rule->next();
					$ft->assign(array(
						'RULE' => date('G:i A',$rule->f('start_time')).' - '.date('g:i A',$rule->f('end_time')),
						'DETAILS' => date('G:i A',$triggered->f('diff')).' - '.date('g:i A',$triggered->f('diff_alt')),
					));
					break;
				case 4://app alert
					$rule = $this->dbu->query("SELECT alert_other.cond,application.description FROM alert_other 
										INNER JOIN application ON application.application_id = alert_other.cond_link 
										WHERE alert_other_id = ".$triggered->f('rule_id'));
					$rule->next();
					$ft->assign(array(
						'RULE' => $rule['description'].' (<b>'.format_time_with_day($rule['cond']*60).'</b>)',
						'DETAILS' => $rule['description'].' (<b>'.format_time_with_day($triggered['diff'] + $rule['cond']*60).'</b>)',
					));
					break;
				case 6:// web alert
					$rule = $this->dbu->query("SELECT alert_other.cond,domain.domain FROM alert_other 
										INNER JOIN domain ON domain.domain_id = alert_other.cond_link 
										WHERE alert_other_id = ".$triggered->f('rule_id'));
					$rule->next();
					$ft->assign(array(
						'RULE' => $rule['domain'].' (<b>'.format_time_with_day($rule['cond']*60).'</b>)',
						'DETAILS' => $rule['domain'].' (<b>'.format_time_with_day($triggered['diff'] + $rule['cond']*60).'</b>)',
					));
					break;
				default:
					$rule = $this->dbu->query("SELECT * FROM alert_other WHERE alert_other_id = ".$triggered->f('rule_id'));
					$rule->next();
					$ft->assign(array(
						'RULE' => format_time_with_day($rule['cond']*60),
						'DETAILS' => format_time_with_day($triggered['diff'] + $rule['cond']*60),
					));
					break;
			}
			
			$ft->parse('UA_ROW_OUT','.ua_row');
			$i++;
		}
		
		if(!$i){
			$ld['error'].='No data to export!<br>';
			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Alerts').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
	
		if ($_GET['format'] == 'xls') {
		create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents);
			if($_SESSION['attachment_name'])
				{
						$tmp = ini_get('upload_tmp_dir');
						file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND); 
				}
			else {header('Pragma: public'); 	
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT'); 
			header('Cache-Control: no-store, no-cache, must-revalidate'); 
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); 
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"');
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	
	/****************************************************************
	* function sequence(&$ld)                                     *
	****************************************************************/
	function sequence(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->timeline_validate($ld))
		{
			return false;
		}
		
		$ft = new ft(ADMIN_PATH.MODULE.'reports/');
		$ft->define(array('main'=>'sequence.csv'));
		$ft->define_dynamic('template_row','main');
		
		$pieces = explode('-',$_SESSION['filters']['time']['time']);
		
		global $glob;
		$nodes = explode('-',$glob['f']);
		$department_id = reset($nodes);
		unset($nodes);
		
		if(count($pieces) == 2){
			$start_time = strtotime(reset($pieces));
			$end_time = strtotime(end($pieces));
			if(($end_time - $start_time) > 86400){
				$end_time = $start_time + 86400;
			}
			$_SESSION['filters']['time']['time'] = date('n/d/Y g:i A',$start_time).' - '.date('n/d/Y g:i A',$end_time);
		}
		
		$pieces = explode('-',$_SESSION['filters']['f']);
		$type = substr($pieces[0],0,1);
		$pieces[0] = substr($pieces[0],1);

		if(count($pieces) == 1)
		{
			$department_id = current($pieces);
			$member = $this->dbu->row("SELECT member.department_id,member.member_id,computer.computer_id FROM member 
			INNER JOIN computer2member ON computer2member.member_id = member.member_id
			INNER JOIN computer ON computer.computer_id = computer2member.computer_id
			WHERE computer.department_id = ? AND member.department_id = ?",array($department_id,$department_id));
			
			if(empty($member))
			{
				//no member? just fetch something then..
				$member = $this->dbu->row("SELECT member.department_id,member.member_id,computer.computer_id FROM member 
				INNER JOIN computer2member ON computer2member.member_id = member.member_id
				INNER JOIN computer ON computer.computer_id = computer2member.computer_id
				LIMIT 1");
				$type = 's';
			}
			
			$_SESSION['filters']['f'] = $type.$member['department_id'].'-'.$member['computer_id'].'-'.$member['member_id'];
		}
		
		$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time']);
		extract($filters,EXTR_OVERWRITE);
		
		$categories = get_categories($_SESSION['filters']['f'],0);
		
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		
		$ft->assign(array(
			'TITLE' => 'Timeline',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
				
		$additional_application_filter = '';
		
		if($ld['app'] && !empty($ld['app']) )
		{
			$additional_application_filter .= " AND application.application_id = ".$ld['app'];
		}
		
		if($ld['win'] && !empty($ld['win']) )
		{
			$additional_application_filter .= " AND window.window_id = ".$ld['win'];
			$additional_application_filter .= " AND session_log.active = ".$ld['wact'];
		}
		
		//add the extra filter for the minutes
		$matches = array();
		preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$_SESSION['filters']['time']['time'],$matches);
		$pieces = array_shift($matches);
		switch (count($pieces)){
			case 1:
				$time = strtotime(current($pieces));
				$app_filter .= ' AND session_log.start_time >= '.$time;
				break;
			case 2:
				$start_time = strtotime(reset($pieces));
				$end_time = strtotime(end($pieces));
				
				$app_filter .= ' AND (session_log.start_time BETWEEN '.$start_time.' AND '.$end_time.')';
				break;
		}

		$this->dbu->query("SELECT application.description, 
								application.application_id,
								window.name,
								session_log.* 
								FROM session_log 
								INNER JOIN window ON window.window_id = session_log.window_id
								INNER JOIN application ON application.application_id = session_log.application_id 
								INNER JOIN session ON session.session_id = session_log.session_id 
								".$app_join."
								WHERE 6=6 ".$app_filter.$additional_application_filter." ORDER BY start_time ASC");

		$data = array();
		$i = 0;
		
		while ($this->dbu->move_next()){
			
			$type = '';
			switch ($this->dbu->f('type_id')){
				case 1:
					$type = 'chats';
					break;
				case 2:
					$type = 'documents';
					break;
				case 3:
					$type = 'sites';
					break;
				default:
					$type = 'windows';	
			}
			
			if($this->dbu->f('duration') == 0){
				continue;
			}
			$index = count($data);
			
			if($index != 0)
			{
			
				$indexprev = $index-1;
				
				if($data[$indexprev]['APP_ID'] == $this->dbu->f('application_id') && $data[$indexprev]['EXPAND'][0]['window_id'] == $this->dbu->f('window_id'))
				{
					$data[$indexprev]['TIME'] += $this->dbu->f('duration');
					$data[$indexprev]['INTERVAL_END'] = date('g:i:s A',$this->dbu->f('end_time'));
					$data[$indexprev]['TIME_FORMATED'] = format_time($data[$indexprev]['TIME']);
					$str = $data[$indexprev]['TIMELINE_INFO'];
					$end = $data[$indexprev]['END_TIME'];
					$str = str_replace('end='.$end,'end='.$this->dbu->f('end_time'),$str);
					$data[$indexprev]['TIMELINE_INFO'] = $str;
					$data[$indexprev]['END_TIME'] = $this->dbu->f('end_time');			
				$data[$indexprev]['EXPAND'][count($data[$indexprev]['EXPAND'])-1]['window_duration'] += $this->dbu->f('duration');
					
					if(in_array($this->dbu->f('active'),array(2,3)))
					{
						array_push($data[$indexprev]['private_end'], date('g:i:s A',$this->dbu->f('end_time')));
					}
				}
				else
				{
					$cat_name = $ft->lookup('Uncategorised');
					$cat_id = 1;
					
					if(isset($categories[$this->dbu->f('application_id').'-0'])){
						$cat_name = $ft->lookup($categories[$this->dbu->f('application_id').'-0']['category']);
						$cat_id = $categories[$this->dbu->f('application_id').'-0']['category_id'];
					}
					
					array_push($data, array(
						'APP_ID' => $this->dbu->f('application_id'),
						'APPLICATION' => decode_numericentity($this->dbu->f('description')),
						'ACTIVITY_TYPE' => $this->dbu->f('active') == 0 || $this->dbu->f('active') == 1 ? array(0,1) : array(2,3),
						'TIME' => $this->dbu->f('duration'),
						'INTERVAL_START' => date('g:i:s A',$this->dbu->f('start_time')),
						'INTERVAL_END' => date('g:i:s A',$this->dbu->f('end_time')),
						'TIME_FORMATED' => format_time($this->dbu->f('duration')),
						'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$this->dbu->f('session_id').'&app='.$this->dbu->f('application_id').'&start='.$this->dbu->f('start_time').'&type='.$this->dbu->f('type_id').'&end='.$this->dbu->f('end_time'),
						'APPLICATION_ID' => $this->dbu->f('application_id'),
						'CATEGORY' => decode_numericentity($cat_name),
						'CATEGORY_ID' => $cat_id,
						'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$this->dbu->f('application_id'),
						'END_TIME' => $this->dbu->f('end_time'),
						'WINDOWS_TYPE' => $type,
						'ID' => $this->dbu->f('application_id'),
						'EXPAND' => array()
						));	
					
					if (is_array($data[$index]['EXPAND'])) {
						array_push($data[$index]['EXPAND'],array(
							'window_id'	=> $this->dbu->f('window_id'),
							'window_active' => $this->dbu->f('active'),
							'window_name' => $this->dbu->f('name'),
							'window_duration' => $this->dbu->f('duration'),
							'window_filter_link' => 'index.php?pag=sequence&app='.$this->dbu->f('application_id').'&win='.$this->dbu->f('window_id').'&wact='.$this->dbu->f('active')
						));
					}
				}
			}
			else
			{
				$cat_name = $ft->lookup('Uncategorised');
				$cat_id = 1;
				
				if(isset($categories[$this->dbu->f('application_id').'-0'])){
					$cat_name = $ft->lookup($categories[$this->dbu->f('application_id').'-0']['category']);
					$cat_id = $categories[$this->dbu->f('application_id').'-0']['category_id'];
				}
				
				array_push($data, array(
					'APP_ID' => $this->dbu->f('application_id'),
					'APPLICATION' => decode_numericentity($this->dbu->f('description')),
					'TIME' => $this->dbu->f('duration'),
					'ACTIVITY_TYPE' => $this->dbu->f('active') == 0 || $this->dbu->f('active') == 1 ? array(0,1) : array(2,3),
					'INTERVAL_START' => date('g:i:s A',$this->dbu->f('start_time')),
					'INTERVAL_END' => date('g:i:s A',$this->dbu->f('end_time')),
					'TIME_FORMATED' => format_time($this->dbu->f('duration')),
					'WINDOWS_TYPE' => $type,
					'APPLICATION_ID' => $this->dbu->f('application_id'),
					'CATEGORY' => decode_numericentity($cat_name),
					'CATEGORY_ID' => $cat_id,
					'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$this->dbu->f('application_id'),
					'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$this->dbu->f('session_id').'&app='.$this->dbu->f('application_id').'&start='.$this->dbu->f('start_time').'&type='.$this->dbu->f('type_id').'&end='.$this->dbu->f('end_time'),
					'END_TIME' => $this->dbu->f('end_time'),
					'ID' => $this->dbu->f('application_id'),
				'EXPAND' => array()
				));	
			
			if (is_array($data[$index]['EXPAND'])) {
				array_push($data[$index]['EXPAND'],array(
					'window_id'	=> $this->dbu->f('window_id'),
					'window_active'	=> $this->dbu->f('active'),
					'window_name'	=> $this->dbu->f('name'),
					'window_duration'	=> $this->dbu->f('duration'),
					'window_filter_link' => 'index.php?pag=sequence&app='.$this->dbu->f('application_id').'&win='.$this->dbu->f('window_id').'&wact='.$this->dbu->f('active')
				));
			}
			}
		}

	// clean up the array
	foreach($data as $key => $value) {
		if (!$value['APP_ID']) {
			unset ($data[$key]);
		}
	}
	$sequences = array();
	$sequencecount = array();
	$sequences_db = $this->dbu->query("SELECT * FROM `sequence_dep` WHERE `department_id` = '" . filter_var($department_id, FILTER_SANITIZE_NUMBER_INT) . "'");
	while ($sequences_db->next()){
		$sequences[] = $sequences_db->f("sequencegrp_id");
	}
	foreach ($sequences as $k => $v) {
		$sequence_list = array();
		$sequencename = $this->dbu->field("SELECT `name` FROM `sequence_reports` WHERE `sequencegrp_id` = " . $v);
		$sequencenoise = $this->dbu->field("SELECT `noise` FROM `sequence_reports` WHERE `sequencegrp_id` = " . $v);
		$sequence_list_db = $this->dbu->query("SELECT * FROM `sequence_list` WHERE `sequencegrp_id` = " . $v);
		$sequencecount[$sequencename] = 0;
		while ($sequence_list_db->next()){
			$sequence_list[$sequence_list_db->f("weight")] = array('APP_ID' => $sequence_list_db->f("app_id"), 'window_id' => $sequence_list_db->f("form_id"));
		}
		ksort($sequence_list);
		if ($sequencenoise == 1) {
			$intersect = array_uintersect( $data, $sequence_list, 'compareDeepValue');
			$to_elements = find_sequence_in_timeline($sequence_list, $intersect);
		} else {
			$to_elements = find_sequence_in_timeline($sequence_list, $data);
		}
		$data = apply_sequence_to_timeline($data, $to_elements, $sequencename);
		$sequencecount[$sequencename] = count_sequence_in_timeline($data, $to_elements, $sequencename);
	}

	// echo '<pre>';print_r($data);exit;	
	foreach ($data as $key => $value){
		if(in_array(2,$value['ACTIVITY_TYPE']))
		{
			$value['APPLICATION'] = 'Private Time';
			$value['EXPAND'] = array();
			$value['CATEGORY'] = '';
			$value['WINDOWS_TYPE'] = '';
			$value['APP_FILTER_LINK'] = '#';
			$total_windows = 0;
			$value['INTERVAL_START'] = reset($data[$i]['private_start']);
			$value['INTERVAL_END'] = end($data[$i]['private_end']);
			$value['TIME_FORMATED'] = format_time($data[$i]['private_total']);
		}
		$ft->assign(array(
			'START' => $value['INTERVAL_START'],
			'END' => $value['INTERVAL_END'],
			'HOUR' => $data[$i]['name'],
			'APPLICATION' => $value['APPLICATION'] . ' - ' . html_entity_decode($value['EXPAND'][0]['window_name']),
			'CATEGORY' => $value['SEQUENCENAME'],
		));
		if(in_array(1,$value['ACTIVITY_TYPE']))
		{
			$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			$counter++;
		}
		else if( !$do_not_parse_private_for_this_hour_anymore && in_array(2,$value['ACTIVITY_TYPE']) && !$ld['app'])
		{
			$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			$counter++;
			$do_not_parse_private_for_this_hour_anymore = true;
		}		
	}
		
		if(empty($value)){			
			$ft->assign(array(
				'NO_DATA_MESSAGE' => $ft->lookup('No data to display for your current filters'),
				'HIDE_START' => '[!H!]',
				'HIDE_END' => '[!/H!]',
			));
		}else {
			$ft->assign(array(
				'NO_DATA_MESSAGE' => '',
				'HIDE_START' => '',
				'HIDE_END' => '',
			));
		}
		$output_file = $ft->lookup('Sequence').'.csv';
		$ft->parse('CONTENT','main');
		
		$file_contents = $ft->fetch('CONTENT');
		unset($ft);
		
		if ($_GET['format'] == 'xls') {
			create_xls($file_contents, $output_file);
		} else {
			$size_in_bytes = strlen($file_contents); 	
			if($_SESSION['attachment_name']) 	
					{ 			
					$tmp = ini_get('upload_tmp_dir'); 			
					file_put_contents($tmp."/".$_SESSION['attachment_name'], chr(239).chr(187).chr(191).$file_contents,FILE_APPEND);  
					}
			
			else{
			header('Pragma: public');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: none'); 
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); 
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); 
			header('Content-Disposition: attachment; filename="' . $output_file . '";size='.$size_in_bytes); 
			echo chr(239).chr(187).chr(191).$file_contents; }
		}
		$ld['error'].='Report has been successfully exported!';
		exit();
		
		return true;
	}
	/****************************************************************
	* function alerts_validate(&$ld)                            *
	****************************************************************/
	function alerts_validate(&$ld)
	{
		$is_ok = true;
		
		return $is_ok;
	}
}//end class
