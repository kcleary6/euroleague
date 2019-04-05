<?php
	include "databaseconnect.php";
	
	mysql_query("TRUNCATE TABLE game");
	mysql_query("TRUNCATE TABLE player");
	mysql_query("TRUNCATE TABLE statistics");
	mysql_query("TRUNCATE TABLE team");
	
	
	// DEFINIRAJ URL (sifru utakmice)
	for($sezona = 2012; $sezona <= 2019; $sezona++) {
		for($indeks = 001; $indeks <= 260; $indeks++) {
			echo $indeks."<br />";
			$url = 'http://www.euroleague.net/main/results/showgame?gamecode='.$indeks.'&seasoncode=E'.$sezona;
			$output = file_get_contents($url);

			if (preg_match('@<title>404 Not Found</title>@i', $output)){ // ovo ne radi, ali neka bude svejedno za neku drugu skriput, kontrola je napravljena u retku 36 (if($godina == '') continue;)
				
			} else {
				// ******************************************************************************************************
				// PRONAĐI DATUM UTAKMICE
				// ******************************************************************************************************
				$datum = explode ('<div class="date cet">', $output);
				$datum = explode ("</div>", $datum[1]);
				$datum = explode (" ", $datum[0]);
				
				if(trim($datum[0]) == 'January') $mjesec = 1; if(trim($datum[0]) == 'February') $mjesec = 2;
				if(trim($datum[0]) == 'March') $mjesec = 3; if(trim($datum[0]) == 'April') $mjesec = 4;
				if(trim($datum[0]) == 'May') $mjesec = 5; if(trim($datum[0]) == 'June') $mjesec = 6;
				if(trim($datum[0]) == 'July') $mjesec = 7; if(trim($datum[0]) == 'August') $mjesec = 8;
				if(trim($datum[0]) == 'September') $mjesec = 9; if(trim($datum[0]) == 'October') $mjesec = 10;
				if(trim($datum[0]) == 'November') $mjesec = 11; if(trim($datum[0]) == 'December') $mjesec = 12;
				
				$dan = trim(str_replace(',', '', $datum[1]));
				$godina = trim($datum[2]);
				
				if($godina == '') continue;
				
				$datum = $godina."-".$mjesec."-".$dan;
				$gamid = $sezona."-".$indeks;
				// ******************************************************************************************************
				// KRAJ PRONALASKA DATUMA UTAKMICE
				// ******************************************************************************************************
				
				
				// ******************************************************************************************************
				// PRONAĐI DOMAĆINA I GOSTA
				// ******************************************************************************************************
				$timovi = explode ('<span class="name">', $output); $timovi = explode ('</span>', $timovi[1]);

				$domacin = trim($timovi[0]);  // ime momčadi domaćina

				$timovi = explode ('<span class="name">', $output); $timovi = explode ('</span>', $timovi[2]);
	
				$gost = trim($timovi[0]);  // ime momčadi gosta			
				// ******************************************************************************************************
				// KRAJ PRONALASKA DOMACINA I GOSTA
				// ******************************************************************************************************
				
				
				// ******************************************************************************************************
				// PRONAĐI REZULTAT I REZULTAT PO ČETVRTINAMA
				// ******************************************************************************************************
				$rezultat = explode ('<span class="score">', $output);
				$rezultat = explode ('</span>', $rezultat[1]);
				
				$rezultat_domacin = trim($rezultat[0]);
				
				$rezultat = explode ('<span class="score">', $output);
				$rezultat = explode ('</span>', $rezultat[2]);
				
				$rezultat_gost = trim($rezultat[0]);
					
				$rezultat = $rezultat_domacin.":".$rezultat_gost;
				
				// domacin cetvrtine
				$cetvrtine = explode ('<td class="PartialsClubNameContainer">', $output);
				$cetvrtine = explode ('</td>', $cetvrtine[1]);
				$prva = explode ('">', $cetvrtine[1]);
				$domacin_prva = trim($prva[1]);
				
				$druga = explode ('">', $cetvrtine[2]);
				$domacin_druga = trim($druga[1]);
				
				$treca = explode ('">', $cetvrtine[3]);
				$domacin_treca = trim($treca[1]);
				
				$cetvrta = explode ('">', $cetvrtine[4]);
				$domacin_cetvrta = trim($cetvrta[1]);
				
				// gost cetvrtine
				$cetvrtine = explode ('<td class="PartialsClubNameContainer">', $output);
				$cetvrtine = explode ('</td>', $cetvrtine[2]);
				$prva = explode ('">', $cetvrtine[1]);
				$gost_prva = trim($prva[1]);
				
				$druga = explode ('">', $cetvrtine[2]);
				$gost_druga = trim($druga[1]);
				
				$treca = explode ('">', $cetvrtine[3]);
				$gost_treca = trim($treca[1]);
				
				$cetvrta = explode ('">', $cetvrtine[4]);
				$gost_cetvrta = trim($cetvrta[1]);
				
				$prva = $domacin_prva.":".$gost_prva;
				$druga = $domacin_druga.":".$gost_druga;
				$treca = $domacin_treca.":".$gost_treca;
				$cetvrta = $domacin_cetvrta.":".$gost_cetvrta;

				// ******************************************************************************************************
				// KRAJ PRONALASKA REZULTATA
				// ******************************************************************************************************
				
				
				// ******************************************************************************************************
				// UNOS MOMCADI, UTAKMICA I REZULTATA
				// ******************************************************************************************************
				
				// provjeri da li postoje momcadi unesene u bazu podataka, ako nisu, unesi!
				$postoji_domacin = mysql_query("SELECT * FROM team WHERE name = '$domacin'");
				$postoji_gost = mysql_query("SELECT * FROM team WHERE name = '$gost'");
				
				if(mysql_num_rows($postoji_domacin) == 0) mysql_query("INSERT INTO team (name) VALUES ('$domacin')");
				if(mysql_num_rows($postoji_gost) == 0) mysql_query("INSERT INTO team (name) VALUES ('$gost')");

				// provjeri je li utakmica unesena, ako nije, unesi!
				$domacin_sifra = mysql_fetch_array(mysql_query("SELECT * FROM team WHERE name ='$domacin'"));
				$gost_sifra = mysql_fetch_array(mysql_query("SELECT * FROM team WHERE name ='$gost'"));
				
				$datum_sql = $godina."-".$mjesec."-".$dan;

				// Total team stats
				$team_stats = explode('Totals</td>',$output);
				$home_stats = explode('/tr>',$team_stats[1]);
				$stat = explode('<td>',$home_stats[0]);
				$Game_total_time = explode('TimePlayed">',$stat[1]);
				$Game_total_time = explode('</span>',$Game_total_time[1]);
				$Game_total_time=$Game_total_time[0];
				
				$home_score = explode('">',$stat[2]);
				$home_score = explode('</span>',$home_score[1]);
				$home_score=$home_score[0];

				$Tpt_at = explode('">',$stat[3]);
				$Tpt_at = explode('</span>',$Tpt_at[1]);
				$Tpt_at = explode('/',$Tpt_at[0]);
				$Tpt_made=$Tpt_at[0];
				$Tpt_at = $Tpt_at[1];

				$Thpt_at = explode('">',$stat[4]);
				$Thpt_at = explode('</span>',$Thpt_at[1]);
				$Thpt_at = explode('/',$Thpt_at[0]);
				$Thpt_made=$Thpt_at[0];
				$Thpt_at = $Thpt_at[1];

				$Ft_at = explode('">',$stat[5]);
				$Ft_at = explode('</span>',$Ft_at[1]);
				$Ft_at = explode('/',$Ft_at[0]);
				$Ft_made=$Ft_at[0];
				$Ft_at = $Ft_at[1];

				$OfR = explode('">',$stat[6]);
				$OfR = explode('</span>',$OfR[1]);
				$OfR=$OfR[0];

				$DeR = explode('">',$stat[7]);
				$DeR = explode('</span>',$DeR[1]);
				$DeR=$DeR[0];

				$Ass = explode('">',$stat[9]);
				$Ass = explode('</span>',$Ass[1]);
				$Ass=$Ass[0];

				$stl = explode('">',$stat[10]);
				$stl = explode('</span>',$stl[1]);
				$stl=$stl[0];

				$to = explode('">',$stat[11]);
				$to = explode('</span>',$to[1]);
				$to=$to[0];

				$blkf = explode('">',$stat[12]);
				$blkf = explode('</span>',$blkf[1]);
				$blkf=$blkf[0];

				$blka = explode('">',$stat[13]);
				$blka = explode('</span>',$blka[1]);
				$blka=$blka[0];

				$flf = explode('">',$stat[14]);
				$flf = explode('</span>',$flf[1]);
				$flf=$flf[0];

				$fla = explode('">',$stat[15]);
				$fla = explode('</span>',$fla[1]);
				$fla=$fla[0];

				//Away

				$away_stats = explode('/tr>',$team_stats[2]);
				$stat = explode('<td>',$away_stats[0]);
								
				$away_score = explode('">',$stat[2]);
				$away_score = explode('</span>',$away_score[1]);
				$away_score=$away_score[0];

				$Tpt_at2 = explode('">',$stat[3]);
				$Tpt_at2 = explode('</span>',$Tpt_at2[1]);
				$Tpt_at2 = explode('/',$Tpt_at2[0]);
				$Tpt_made2=$Tpt_at2[0];
				$Tpt_at2 = $Tpt_at2[1];

				$Thpt_at2 = explode('">',$stat[4]);
				$Thpt_at2 = explode('</span>',$Thpt_at2[1]);
				$Thpt_at2 = explode('/',$Thpt_at2[0]);
				$Thpt_made2=$Thpt_at2[0];
				$Thpt_at2 = $Thpt_at2[1];

				$Ft_at2 = explode('">',$stat[5]);
				$Ft_at2 = explode('</span>',$Ft_at2[1]);
				$Ft_at2 = explode('/',$Ft_at2[0]);
				$Ft_made2=$Ft_at2[0];
				$Ft_at2 = $Ft_at2[1];

				$OfR2 = explode('">',$stat[6]);
				$OfR2 = explode('</span>',$OfR2[1]);
				$OfR2=$OfR2[0];

				$DeR2 = explode('">',$stat[7]);
				$DeR2 = explode('</span>',$DeR2[1]);
				$DeR2=$DeR2[0];

				$Ass2 = explode('">',$stat[9]);
				$Ass2 = explode('</span>',$Ass2[1]);
				$Ass2=$Ass2[0];

				$stl2 = explode('">',$stat[10]);
				$stl2 = explode('</span>',$stl2[1]);
				$stl2=$stl2[0];

				$to2 = explode('">',$stat[11]);
				$to2 = explode('</span>',$to2[1]);
				$to2=$to2[0];

				$blkf2 = explode('">',$stat[12]);
				$blkf2 = explode('</span>',$blkf2[1]);
				$blkf2=$blkf2[0];

				$blka2 = explode('">',$stat[13]);
				$blka2 = explode('</span>',$blka2[1]);
				$blka2=$blka2[0];

				$flf2 = explode('">',$stat[14]);
				$flf2 = explode('</span>',$flf2[1]);
				$flf2=$flf2[0];

				$fla2 = explode('">',$stat[15]);
				$fla2 = explode('</span>',$fla2[1]);
				$fla2=$fla2[0];



				//Add to database
				
				$postoji_tekma = mysql_query("SELECT * FROM game WHERE home_id = '$domacin_sifra[0]' AND guest_id = '$gost_sifra[0]' AND date = '$datum_sql'AND league_id = 1");
				
				
                if(mysql_num_rows($postoji_tekma) == 0) mysql_query("INSERT INTO game (game_id, home_id, guest_id, date, result, 1_quarter, 2_quarter, 3_quarter, 4_quarter, game_time, points_h, 2fgm_h, 2fga_h, 3fgm_h, 3fga_h,ftm_h, fta_h, off_reb_h, def_reb_h, asist_h, st_h, tover_h, bl_h, bl_ag_h, f_h, f_drawn_h,points_a, 2fgm_a, 2fga_a, 3fgm_a, 3fga_a,ftm_a, fta_a, off_reb_a, def_reb_a, asist_a, st_a,tover_a, bl_a, bl_ag_a, f_a, f_drawn_a,league_id) 
                                                                    VALUES ('$gamid','$domacin_sifra[0]', '$gost_sifra[0]', '$datum_sql', '$rezultat', '$prva', '$druga', '$treca', '$cetvrta', '$Game_total_time','$home_score','$Tpt_made','$Tpt_at','$Thpt_made','$Thpt_at','$Ft_made','$Ft_at','$OfR','$DeR','$Ass','$stl','$to','$blkf','$blka','$flf','$fla','$away_score','$Tpt_made2','$Tpt_at2','$Thpt_made2','$Thpt_at2','$Ft_made2','$Ft_at2','$OfR2','$DeR2','$Ass2','$stl2','$to2','$blkf2','$blka2','$flf2','$fla2',1)"); 
				// ******************************************************************************************************
				// KRAJ UNOSA MOMCADI, UTAKMICA I REZULTATA
				// ******************************************************************************************************
				
            }
        }
    }
?>