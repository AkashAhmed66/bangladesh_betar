<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\User;
use App\Models\WatchCategory;
use App\Models\WatchShow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PortalContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->installImages();
        $creatorId = User::query()->where('email', 'admin@betar.gov.bd')->value('id');

        $news = [
            // --- Bangladesh ---
            ['rail-link-connects-river-regions', 'New rail link brings river communities closer to the capital', 'The expanded route is expected to shorten journeys, improve regional trade and give more passengers access to reliable public transport.', 'Bangladesh', 'news-hero.png', 4, 12, 7820, true, [
                "A newly expanded rail connection has begun carrying passengers across one of the country's busiest river corridors, creating a faster link between regional towns and Dhaka.",
                'Transport planners say the service is designed to reduce road pressure while making education, healthcare and markets easier to reach. More services are expected to be added after the first operating review.',
                'Local businesses welcomed the opening and said predictable journey times could make it easier to move fresh produce and small manufactured goods between districts.',
            ]],
            ['coastal-volunteers-complete-shelter-drill', 'Coastal volunteers complete early-season shelter drill', 'Community teams checked first-aid supplies, evacuation routes and communications before the next period of severe weather.', 'Bangladesh', 'news-coast.png', 5, 48, 3240, false, [
                'Volunteer groups in coastal communities have completed a coordinated readiness exercise focused on cyclone shelter access and household communication.',
                'Teams inspected emergency supplies and practised supporting older residents, children and people with disabilities during an evacuation.',
                'Organisers said the exercise will be repeated in remote areas where travel becomes difficult during heavy rain.',
            ]],
            ['dhaka-metro-expands-evening-schedule', 'Dhaka Metro expands evening schedule to meet commuter demand', 'Additional train frequency and extended terminal operating hours ease rush-hour congestion across key city corridors.', 'Bangladesh', 'news-hero.png', 3, 90, 8910, true, [
                'Dhaka Mass Transit authorities have added new evening services to cater to rising commuter numbers returning from commercial hubs.',
                'Passenger feedback highlighted significant reductions in station waiting times and improved interchange convenience.',
                'Feeder bus routes are being synchronized with metro arrivals to provide seamless last-mile connectivity for urban travelers.',
            ]],
            ['sylhet-tea-estates-adopt-solar-irrigation', 'Sylhet tea estates adopt solar-powered micro-irrigation systems', 'Greener energy solutions protect fragile mountain ecosystems while sustaining premium tea output through dry spells.', 'Bangladesh', 'news-rice.png', 4, 340, 1980, false, [
                'Several heritage tea plantations in the Sylhet region have installed solar-powered micro-sprinkler networks across high-elevation slopes.',
                'Plantation managers noted that consistent moisture delivery prevented leaf scorch during recent unseasonal dry spells.',
                'The initiative also reduces reliance on diesel pumps, lowering operational emissions and water runoff into nearby stream beds.',
            ]],
            ['chattogram-port-sets-record-container-throughput', 'Chattogram port sets record container throughput after digital upgrades', 'Automated gate clearances and smart yard tracking cut vessel turnaround times by over twenty percent.', 'Bangladesh', 'watch-river.png', 4, 720, 2450, false, [
                'Chattogram Port Authority reported record cargo handling metrics following the rollout of automated terminal management software.',
                'Importers and shipping lines commended the streamlined customs integrations and round-the-clock clearance windows.',
                'Port officials affirmed ongoing investments in deep-draft berths to accommodate next-generation container carriers.',
            ]],

            // --- Politics ---
            ['parliament-passes-digital-public-service-reform-bill', 'Parliament passes comprehensive digital public service reform bill', 'New legislation establishes citizen service guarantees, automated grievance redressal and unified data interoperability.', 'Politics', 'news-hero.png', 5, 25, 6420, true, [
                'The National Parliament has unanimously passed landmark legislation modernizing government-to-citizen digital delivery standards.',
                'Under the new framework, essential civil certificates, land registry extracts, and utility permits must meet strict statutory response times.',
                'Lawmakers across party lines praised the inclusion of strong privacy safeguards and open accountability dashboards for district administration.',
            ]],
            ['election-commission-upgrades-biometric-voter-verification', 'Election Commission upgrades biometric voter verification infrastructure', 'Updated field devices enhance accuracy, accessibility and cybersecurity for upcoming nationwide local elections.', 'Politics', 'news-tech.png', 4, 160, 4110, false, [
                'The Election Commission has unveiled updated voter authentication kits designed for rapid verification in rural and urban polling centres.',
                'The new portable units feature improved offline encryption, high-resolution fingerprint sensors, and extended battery life for remote stations.',
                'Civic observers and political representatives attended public demonstration trials held across multiple division headquarters.',
            ]],
            ['local-government-dialogue-focuses-on-civic-transparency', 'Local government dialogue focuses on civic budget transparency', 'Union Parishad leaders and community representatives review participatory planning mechanisms and municipal spending.', 'Politics', 'watch-hero.png', 4, 410, 1820, false, [
                'A national summit on grassroots governance convened municipal mayors and Union Parishad chairpersons to share participatory budgeting models.',
                'Civic leaders presented case studies where citizen open-floor forums shaped priority allocations for village roads, drainage, and street lighting.',
                'The ministry affirmed plans to tie annual development incentive grants to verified community engagement metrics.',
            ]],
            ['all-party-climate-caucus-calls-for-delta-funding', 'All-party parliamentary caucus calls for accelerated delta resilience funding', 'Lawmakers urge bilateral and global multilateral partners to prioritize grants over loans for river protection projects.', 'Politics', 'news-coast.png', 5, 860, 3190, true, [
                'Members of the Parliamentary Climate Caucus released a joint declaration advocating increased sovereign grants for river embankment fortifications.',
                'The declaration underscores Bangladesh’s pioneering role in adaptation planning and emphasizes the urgency of protecting vulnerable coastal constituencies.',
                'A delegation of lawmakers is scheduled to present the resolution at upcoming international climate finance roundtables.',
            ]],

            // --- Business ---
            ['aman-harvest-reaches-local-markets', 'Strong Aman harvest begins reaching local markets', 'Farmers across several northern districts report healthy yields after a season of careful water management.', 'Business', 'news-rice.png', 3, 35, 5420, true, [
                'Freshly harvested Aman rice is arriving at regional markets as growers complete work across the northern districts.',
                'Agriculture officers said local irrigation planning and timely field advice helped many farmers protect their crops through changing weather conditions.',
                'Market observers are monitoring transport and storage costs as the harvest moves from farms to mills and retail centres.',
            ]],
            ['central-bank-eases-remittance-transfer-protocols', 'Central Bank eases remittance transfer protocols for wage earners', 'Simplified digital remittance pipelines offer real-time incentives and lower transaction fees for overseas workers.', 'Business', 'news-tech.png', 4, 110, 9240, true, [
                'Bangladesh Bank has announced revised operational guidelines enabling instant cross-border wallet settlements for non-resident wage earners.',
                'Expatriate workers using verified banking apps will benefit from direct government incentive deposits credited without intermediary delays.',
                'Commercial banks welcomed the reforms, anticipating substantial growth in formal financial inflows over coming quarters.',
            ]],
            ['leather-and-footwear-exports-post-double-digit-growth', 'Leather and footwear exports post double-digit quarterly growth', 'Investments in green tannery certifications and eco-friendly compliance unlock fresh European and Asian buyer demand.', 'Business', 'news-hero.png', 4, 280, 2780, false, [
                'Export figures from the Export Promotion Bureau showed robust growth in finished leather goods and premium athletic footwear shipments.',
                'Industry spokespersons attributed the surge to major upgrades in central effluent treatment systems and sustainable manufacturing protocols.',
                'Manufacturers are expanding vocational design academies to cultivate homegrown product stylists for international fashion houses.',
            ]],
            ['mobile-financial-services-reach-remote-char-islands', 'Mobile financial services expand agent banking to remote char islands', 'Solar-powered mobile terminals bring savings, micro-insurance and agricultural credit to underserved river communities.', 'Business', 'news-tech.png', 3, 590, 3650, false, [
                'Agent banking networks have established dedicated service kiosks across island chars along the Jamuna and Padma rivers.',
                'Local residents can now receive farm subsidies, open savings accounts, and transfer funds without undertaking perilous boat journeys.',
                'Financial literacy workshops conducted in village bazaars are helping women entrepreneurs apply for low-interest micro-enterprise loans.',
            ]],
            ['startups-secure-regional-seed-investments-in-agritech', 'Homegrown agritech startups secure regional seed investments', 'Data-driven crop advisory platforms and cold-chain logistics apps attract early-stage venture funding.', 'Business', 'news-rice.png', 4, 1140, 2150, false, [
                'Two Bangladeshi agriculture-technology startups have raised venture funding to expand farm-to-door distribution channels.',
                'Their platforms utilize smartphone satellite imagery and soil sensor telemetry to help smallholders optimize fertilizer applications.',
                'Investors cited the immense scalability of agricultural digitisation across South Asian delta economies as a primary investment catalyst.',
            ]],

            // --- World ---
            ['river-research-maps-seasonal-change', 'Researchers map how seasonal rivers are changing across borders', 'A new public dataset combines satellite observations with reports from people living beside major waterways.', 'World', 'watch-river.png', 6, 180, 4870, true, [
                'Researchers have released an open dataset showing how river channels and nearby settlements change across the seasons.',
                'The project combines satellite imagery with observations contributed by schools and community groups across regional riparian nations.',
                'Planners hope the information can support safer local infrastructure and better decisions about erosion-prone areas.',
            ]],
            ['un-climate-summit-agrees-on-new-loss-and-damage-facility', 'UN climate summit agrees on operational rules for loss and damage facility', 'Vulnerable delta and island nations secure dedicated windows for urgent climate disaster rehabilitation funding.', 'World', 'news-coast.png', 5, 220, 6130, true, [
                'Delegates at international climate negotiations have finalised administrative guidelines to govern the loss and damage fund.',
                'The framework provides expedited disbursements to developing nations suffering acute losses from extreme weather events and sea-level rise.',
                'Bangladesh’s delegation was widely lauded for coordinating unified positions among least developed countries and small island states.',
            ]],
            ['bangladesh-japan-trade-pact-enters-final-review', 'Bangladesh-Japan economic partnership agreement enters final review', 'Bilateral negotiations conclude on tariff reductions, technological cooperation and workforce skill exchanges.', 'World', 'news-hero.png', 4, 670, 3890, false, [
                'Trade delegations in Tokyo and Dhaka have completed technical consultations on the Comprehensive Economic Partnership Agreement.',
                'The pact aims to bolster industrial manufacturing investments, expand high-tech vocational exchanges, and eliminate duties on key exports.',
                'Commercial chambers in both nations expect the agreement to catalyse joint ventures in automotive parts and renewable energy hardware.',
            ]],
            ['south-asian-regional-power-grid-trials-begin', 'South Asian regional clean energy transmission trials commence', 'Cross-border interconnection enables seasonal power sharing between Himalayan hydroelectric plants and coastal grids.', 'World', 'news-tech.png', 5, 1320, 2940, false, [
                'Power transmission entities have initiated synchronized test flows along the regional high-voltage cross-border transmission corridor.',
                'The trilateral arrangement allows excess summer hydropower from Nepal and Bhutan to feed peak industrial demand in Bangladesh.',
                'Energy analysts regard the milestone as a pivotal breakthrough for regional decarbonisation and cost-effective grid stability.',
            ]],

            // --- Sports ---
            ['bangladesh-clinches-thrilling-t20-series-decider', 'Bangladesh clinches thrilling T20 series decider in final-over finish', 'Disciplined death bowling and resilient middle-order partnerships seal an unforgettable home victory in Mirpur.', 'Sports', 'watch-hero.png', 4, 15, 9780, true, [
                'A packed Sher-e-Bangla National Cricket Stadium erupted in celebration as the national team defended a tense final over against formidable visitors.',
                'The pace attack bowled with pinpoint accuracy in the closing overs, executing yorkers and subtle slower deliveries under intense pressure.',
                'The team captain commended the youngsters for maintaining composure and executing team strategies during high-stakes moments.',
            ]],
            ['national-women-football-team-qualifies-for-asian-cup-stage', 'National women’s football team qualifies for Asian Cup knockout stage', 'Dominant attacking teamwork and defensive composure earn historic qualification on the continental stage.', 'Sports', 'watch-kids.png', 4, 140, 8340, true, [
                'The Bangladesh national women’s football squad created history by advancing to the tournament knockout stages with an unbeaten group record.',
                'Spectacular wing play and resolute goalkeeping thwarted opposition attacks throughout a grueling ninety-minute fixture.',
                'Supporters across the country took to social media and city squares to applaud the players’ inspiring tenacity and sporting excellence.',
            ]],
            ['youth-archery-contingent-claims-gold-at-asiad-qualifiers', 'Youth archery contingent claims gold medals at Asian qualifiers', 'Sharpshooting recurve and compound teams display extraordinary precision in international ranking matches.', 'Sports', 'news-hero.png', 3, 490, 3120, false, [
                'Bangladesh youth archers delivered standout performances, clinching double gold in both individual and mixed team recurve categories.',
                'Coaches credited specialized sports psychology conditioning and modern optical training simulators for the team’s mental resilience.',
                'The national federation confirmed the medallists will now enter intensive training camps ahead of the World Youth Championships.',
            ]],
            ['district-kabaddi-championship-draws-huge-crowds-in-bogura', 'District Kabaddi Championship draws enthusiastic crowds across Bogura', 'Traditional rural teams showcase breathtaking agility, strength and sportsmanship in the annual provincial tournament.', 'Sports', 'watch-music.png', 3, 980, 2670, false, [
                'Thousands of rural sports enthusiasts gathered in Bogura to witness the climax of the divisional traditional Kabaddi championship.',
                'Local clubs fielded balanced squads combining veteran raiders with energetic young university athletes in pulsating matches.',
                'Organizers emphasized that rejuvenating traditional grassroots sports fosters youth fitness and community solidarity across rural districts.',
            ]],

            // --- Entertainment ---
            ['indie-film-on-sundarbans-wins-international-festival-acclaim', 'Indie film celebrating Sundarbans forest life wins international acclaim', 'The poetic cinematic drama highlights human-nature harmony, folklore and the resilience of mangrove honey gatherers.', 'Entertainment', 'watch-river.png', 4, 75, 5840, true, [
                'An independent Bangladeshi feature film set in the deep waterways of the Sundarbans has claimed top honours at a prestigious global film festival.',
                'Critics praised the director’s authentic sound design, immersive cinematography, and non-professional cast drawn directly from local forest communities.',
                'The production team confirmed a nationwide theatrical and community screening tour across universities and divisional auditoriums.',
            ]],
            ['betar-golden-era-musical-archive-digitized-for-streaming', 'Betar’s golden era musical archive digitized for on-demand streaming', 'Master tapes of iconic studio recordings, classical ragas and folk melodies are restored in high-fidelity audio.', 'Entertainment', 'watch-music.png', 5, 210, 7120, true, [
                'Bangladesh Betar has completed digital preservation and noise-reduction remastering for thousands of historic studio recordings from the 1960s and 70s.',
                'Listeners can now explore rare performances by legendary vocalists, instrumental maestros, and celebrated radio drama troupes.',
                'Curators highlighted that digital indexing ensures priceless national cultural assets remain accessible to generations of music lovers worldwide.',
            ]],
            ['new-period-drama-series-depicts-1952-language-movement', 'New television period drama depicts heroic stories of the 1952 Language Movement', 'Meticulous production design and poignant performances bring the historic student struggle to prime-time audiences.', 'Entertainment', 'watch-hero.png', 4, 530, 4670, false, [
                'A major new television drama series exploring the cultural and intellectual fervor of the 1952 Bengali Language Movement has premiered.',
                'The screenplay dramatizes real-life historical narratives, student underground publications, and the courage of grassroots cultural activists.',
                'Educational institutions and cultural forums have commended the series for its historical fidelity and stirring emotional resonance.',
            ]],
            ['dhaka-theatre-festival-showcases-young-playwrights', 'Dhaka Theatre Festival showcases daring original works by young playwrights', 'Contemporary stage plays tackle urban life, digital connection, and generational identity in packed auditorium runs.', 'Entertainment', 'watch-kids.png', 3, 1210, 1890, false, [
                'The annual National Theatre Festival opened in Dhaka with a vibrant lineup of original stage plays penned by emerging dramatists.',
                'Experimental stagecraft, live acoustic folk instruments, and minimalist set designs drew standing ovations from enthusiastic theatregoers.',
                'Festival directors announced travel fellowships to support promising production troupes touring their plays across regional district towns.',
            ]],

            // --- Jobs ---
            ['bcs-recruitment-circular-announces-new-technical-cadres', 'PSC announces updated recruitment circular with specialized technical cadres', 'Civil service intake expands opportunities in data analytics, environmental science and cybersecurity engineering.', 'Jobs', 'news-tech.png', 4, 45, 9460, true, [
                'The Public Service Commission has released its latest recruitment notice featuring newly designated technical cadres for public administration.',
                'Candidates with backgrounds in software engineering, statistics, and ecological management are encouraged to apply for dedicated specialist tracks.',
                'Officials reiterated strict adherence to transparent, computer-assisted preliminary screenings and merit-driven viva evaluations.',
            ]],
            ['it-freelancing-skill-hubs-launched-in-twenty-districts', 'IT freelancing and remote skill training hubs launched in twenty districts', 'Government-backed digital training centers equip university graduates with skills for global remote freelancing markets.', 'Jobs', 'news-hero.png', 4, 195, 6780, true, [
                'The ICT Division has inaugurated twenty state-of-the-art vocational training academies offering subsidized courses in web development and UX design.',
                'Enrolled trainees receive high-speed broadband access, international mentor sessions, and hands-on guidance in securing remote global contracts.',
                'Graduates from initial pilot batches have already begun contributing significant export service revenue through formal banking channels.',
            ]],
            ['polytechnic-graduates-gain-fast-track-industrial-apprenticeships', 'Polytechnic graduates gain fast-track industrial apprenticeships nationwide', 'Public-private partnership links diploma engineers directly with automotive, electrical and manufacturing plants.', 'Jobs', 'news-tech.png', 3, 620, 3420, false, [
                'A new industrial internship agreement guarantees six-month paid apprenticeships for thousands of state polytechnic diploma graduates.',
                'Participating conglomerates provide hands-on factory floor training, robotics maintenance exposure, and pathways to permanent technician roles.',
                'Education authorities noted the initiative bridges longstanding industry-academia gaps and enhances domestic engineering capabilities.',
            ]],
            ['primary-teacher-recruitment-results-published-nationwide', 'Primary teacher recruitment results published nationwide with merit quotas', 'Thousands of qualified candidates appointed to rural elementary schools to strengthen foundational education.', 'Jobs', 'watch-kids.png', 3, 1050, 8150, false, [
                'The Directorate of Primary Education has published final merit lists for the nationwide recruitment of primary school assistant teachers.',
                'The transparent selection process utilized biometric verification and automated score tabulations to ensure complete integrity.',
                'Newly appointed teachers will undergo an intensive orientation program covering child-centered pedagogy and digital classroom tools.',
            ]],

            // --- Lifestyle ---
            ['student-robotics-team-heads-to-regional-final', 'Student robotics team heads to regional innovation final', 'The university team built a low-cost inspection rover using locally available components and open-source tools.', 'Lifestyle', 'news-tech.png', 4, 60, 4560, true, [
                'A student engineering team has qualified for a regional innovation final with a compact rover designed to inspect difficult indoor spaces.',
                'The prototype combines affordable sensors with locally sourced parts, allowing the students to repair and adapt it without specialist equipment.',
                'The team hopes the project will encourage more schools and universities to create practical robotics clubs.',
            ]],
            ['community-health-clinics-introduce-telemedicine-counseling', 'Community health clinics introduce remote telemedicine and mental wellbeing counseling', 'Village healthcare outposts connect rural patients with specialist doctors and psychologists via high-definition video links.', 'Lifestyle', 'news-hero.png', 4, 240, 3890, true, [
                'Rural community clinics across several northern upazilas have begun offering weekly telemedicine consultation sessions with specialist doctors.',
                'Patients receive free diagnostic consultations, prescription guidance, and confidential mental health counseling previously unavailable locally.',
                'Health workers reported overwhelming positive responses from mothers, seniors, and students seeking timely health advice.',
            ]],
            ['eco-tourism-trails-gain-popularity-in-bandarban-hills', 'Community-led eco-tourism trails gain popularity in Bandarban hill tracts', 'Sustainable trekking routes support indigenous homestays, conservation guides and traditional organic cuisine.', 'Lifestyle', 'watch-river.png', 4, 780, 2970, false, [
                'Eco-conscious travellers are flocking to newly mapped walking trails managed cooperatively by indigenous communities in the Chittagong Hill Tracts.',
                'Guided treks follow strict zero-plastic principles, offering visitors authentic cultural exchanges and serene mountain vistas.',
                'Homestay operators report that sustainable tourism revenue provides vital income for village schools and reforestation nurseries.',
            ]],
            ['traditional-handloom-weavers-bridge-heritage-and-modern-fashion', 'Traditional Jamdani and Khadi weavers bridge heritage craft and modern fashion', 'Artisanal weaving cooperatives collaborate with young designers to create ethical, breathable contemporary garments.', 'Lifestyle', 'news-rice.png', 4, 1400, 2340, false, [
                'Master handloom weavers in Tangail and Sonargaon are collaborating with urban apparel designers to reimagine historic textile patterns.',
                'Using pure organic cotton and natural plant dyes, the cooperatives produce lightweight fabrics celebrated for durability and elegant textures.',
                'Exhibitions in Dhaka and abroad are creating direct fair-trade sales channels that preserve master weaving lineages.',
            ]],

            // --- Video ---
            ['community-radio-expands-agriculture-bulletins', 'Community radio expands daily agriculture bulletins and weather alerts', 'New regional segments will share market prices, weather guidance and advice from agricultural extension officers.', 'Video', 'news-rice.png', 3, 120, 3840, true, [
                'Regional radio bulletins are expanding to provide farmers with more frequent weather, crop and market information.',
                'The short programmes will be broadcast at times chosen with local listeners and repeated for people working away from home during the day.',
                'Producers said listeners will also be able to submit questions for future episodes.',
            ]],
            ['explainer-how-the-bangabandhu-tunnel-changes-southern-logistics', 'Explainer: How the Bangabandhu Tunnel transforms southern trade logistics', 'Visual analysis examines how South Asia’s first underwater expressway connects economic zones, ports and highway networks.', 'Video', 'news-hero.png', 5, 310, 7620, true, [
                'An in-depth video feature breaks down the engineering marvel and economic implications of the Karnaphuli underwater expressway tunnel.',
                'Computer graphics illustrate how freight transport bypasses congested city centers to link industrial parks with Chattogram deep-sea terminals.',
                'Economists and transport engineers discuss projected long-term boosts to regional industrial productivity and regional tourism.',
            ]],
            ['voices-from-the-delta-living-with-the-tides', 'Voices from the Delta: Video documentary on living with seasonal tides', 'Cinematic video report captures the ingenuity, folklore and everyday resilience of river communities on floating homesteads.', 'Video', 'watch-river.png', 4, 840, 4190, false, [
                'A poignant short documentary chronicles daily life aboard traditional houseboats navigating shifting channels in the lower Meghna delta.',
                'Through intimate camera angles and first-person narratives, boat captains and schoolteachers share their deep relationship with the rivers.',
                'The video has sparked widespread discussions online regarding climate adaptation and the cultural heritage of riparian lifestyles.',
            ]],
            ['field-report-inside-the-national-seed-preservation-vault', 'Field Report: Inside the National Seed Preservation Vault in Gazipur', 'Camera crew visits state-of-the-art cryogenic seed banks securing thousands of indigenous crop varieties for the future.', 'Video', 'news-tech.png', 4, 1560, 2680, false, [
                'A rare behind-the-scenes video report takes viewers inside the climate-controlled vaults of the Bangladesh Agricultural Research Institute.',
                'Agronomists demonstrate how heirloom rice varieties, wild legumes, and drought-hardy grains are preserved in liquid nitrogen at sub-zero temperatures.',
                'The facility serves as an indispensable genetic safety net safeguarding national food security against unforeseen ecological shocks.',
            ]],

            // --- Economy ---
            ['remittance-inflows-reach-six-month-high-ahead-of-festivals', 'Remittance inflows reach six-month peak as formal channel transfers climb', 'Expatriate workers send record funds through digital banking pipelines, strengthening national foreign exchange reserves.', 'Economy', 'news-hero.png', 4, 85, 6840, true, [
                'Monthly remittance receipts crossed multi-year benchmarks as overseas wage earners utilized enhanced banking incentives and instant app transfers.',
                'Financial analysts noted the healthy foreign currency reserves provide vital macroeconomic stability for essential commodity imports.',
                'Banks have broadened foreign exchange service desks to provide instantaneous family disbursements across rural upazilas.',
            ]],
            ['inflation-moderates-as-supply-chain-logistics-stabilize', 'Inflation moderates as domestic agricultural supply chain logistics stabilize', 'Coordinated transport corridors and bumper seasonal harvests ease retail consumer price pressures across major metropolitan markets.', 'Economy', 'news-rice.png', 4, 430, 4290, false, [
                'Official statistical bulletins indicate a welcome downward trend in core food inflation indicators over the past quarter.',
                'Market surveillance teams and direct farmer-to-consumer open markets helped curtail unnecessary intermediary markups.',
                'Policy economists advocate sustained investments in decentralized rural cold storage to smooth seasonal food price fluctuations permanently.',
            ]],
            ['renewable-energy-investments-surge-across-industrial-zones', 'Renewable energy investments surge across special economic zones', 'Rooftop solar installations and industrial waste-to-energy projects reduce factory power overheads and carbon footprints.', 'Economy', 'news-tech.png', 5, 1120, 3110, false, [
                'Dozens of export manufacturing facilities have commissioned rooftop solar photovoltaic arrays totaling hundreds of megawatts of clean energy capacity.',
                'Commercial banks provided green refinancing facilities with attractive concessionary interest rates to accelerate industrial solar adoption.',
                'Industrial park authorities project that captive clean power will strengthen export competitiveness under tightening global supply-chain sustainability standards.',
            ]],

            // --- Climate ---
            ['mangrove-afforestation-protects-hundreds-of-coastal-villages', 'Community mangrove afforestation shields hundreds of vulnerable coastal villages', 'Locally managed green belts reduce storm surge velocity and restore biodiverse fish breeding grounds.', 'Climate', 'news-coast.png', 5, 150, 5320, true, [
                'Coastal forest divisions alongside community volunteers have planted dense mangrove saplings along thousands of hectares of mudflats.',
                'Hydrological studies confirm the mature coastal green belt significantly dissipates wave energy during severe tropical depressions.',
                'Villagers participating in the forestry co-management committees also receive alternative livelihood training in sustainable crab farming and apiculture.',
            ]],
            ['early-warning-cyclone-networks-cut-response-times-to-minutes', 'Smart early-warning cyclone networks cut emergency response times to minutes', 'Automated siren towers, radio broadcasts and instant SMS alerts ensure timely evacuation across offshore islands.', 'Climate', 'watch-river.png', 4, 690, 3780, false, [
                'Disaster management authorities have completed upgrades to the automated coastal multi-hazard warning infrastructure.',
                'The system synthesizes meteorological satellite telemetry with ground radar to trigger multilingual siren alerts and village megaphone announcements.',
                'Recent simulation drills confirmed that vulnerable households can be safely accommodated in multi-purpose cyclone shelters well before landfall.',
            ]],
            ['saline-tolerant-crop-varieties-expand-in-southern-polders', 'Saline-tolerant crop varieties expand harvest yields in southern polders', 'Agronomists introduce resilient mustard, pulse and wheat strains allowing farmers to cultivate winter crops on saline soil.', 'Climate', 'news-rice.png', 4, 1450, 2840, false, [
                'Farmers in coastal delta districts are harvesting bumper yields of saline-tolerant winter crops developed by national research institutes.',
                'Lands that previously lay barren after monsoon rice cultivation are now productive year-round, boosting household incomes and soil fertility.',
                'Agricultural extension teams are distributing certified seed packs and bio-fertilizer kits to expand adoption across neighbouring coastal upazilas.',
            ]],

            // --- Culture ---
            ['pahela-baishakh-preparations-begin-with-traditional-mangol-shobhajatra', 'Pahela Baishakh preparations begin with vibrant traditional Mangol Shobhajatra motifs', 'Fine arts students and folk artisans craft colourful masks, peace motifs and giant celebratory effigies.', 'Culture', 'watch-music.png', 4, 175, 7390, true, [
                'Campuses and cultural academies across the country have begun lively preparations for the upcoming Bengali New Year celebrations.',
                'Faculty and student artists at Dhaka University’s Faculty of Fine Arts are creating large-scale sculptural motifs symbolizing harmony, unity and hope.',
                'Cultural enthusiasts and families look forward to joining the world-renowned UNESCO-recognized Mangol Shobhajatra procession at dawn.',
            ]],
            ['baul-music-archive-unveils-rare-recordings-from-kushtia', 'Baul music archive unveils rare historical acoustic recordings from Kushtia', 'Scholars and musicologists digitize oral philosophical songs and Lalon Shah verses for global cultural research.', 'Culture', 'watch-hero.png', 5, 760, 3940, false, [
                'A comprehensive digital repository dedicated to Baul philosophical poetry and acoustic folk music has opened to the public.',
                'The collection includes unreleased reel-to-reel field recordings of veteran ektara and dotara players performing at rural riverside shrines.',
                'International musicologists highlighted the universal spiritual depth and ecological philosophy embedded in Bengal’s mystic musical traditions.',
            ]],
            ['national-book-fair-records-record-footfall-and-youth-authors', 'National Book Fair records unprecedented footfall and surge in young authors', 'Thousands of literature lovers crowd the historic grounds as new poetry, fiction and science books hit the stalls.', 'Culture', 'watch-kids.png', 4, 1620, 5120, false, [
                'The Amar Ekushey Boi Mela witnessed bustling crowds of avid readers, students, and writers browsing hundreds of brightly lit publishing pavilions.',
                'Publishers reported remarkable enthusiasm for contemporary fiction, investigative non-fiction, and children’s science comic series.',
                'Authors and critics engaged in animated open-air literary discussions celebrating the rich heritage of Bengali language and literature.',
            ]],

            // --- Science ---
            ['biochemists-develop-rapid-water-purity-testing-kits', 'Biochemists develop low-cost rapid water purity testing strips for rural communities', 'The portable paper-based sensor detects arsenic, heavy metals and bacterial contamination within minutes.', 'Science', 'news-tech.png', 4, 210, 4610, true, [
                'A team of university biochemists has invented an affordable, easy-to-use water testing strip tailored for field deployment.',
                'The sensor changes colour in the presence of hazardous contaminants, allowing tube-well owners to instantly determine drinking water safety without laboratory equipment.',
                'Public health agencies plan to distribute the testing kits to village sanitation volunteers across flood-prone and coastal districts.',
            ]],
            ['space-research-station-tracks-weather-patterns-with-high-accuracy', 'Space research and remote sensing station tracks weather patterns with high accuracy', 'Indigenous satellite data processing algorithms enhance rainfall forecasts and flood warning lead times.', 'Science', 'news-hero.png', 5, 890, 3450, false, [
                'The Space Research and Remote Sensing Organization has unveiled an upgraded satellite telemetry data processing pipeline.',
                'By integrating geostationary cloud tracking with river basin elevation maps, the station generates hyper-localized monsoon rainfall predictions.',
                'Hydrologists and disaster planners commended the improved five-day advance warnings for mitigating urban flash floods and crop damage.',
            ]],
            ['ai-powered-crop-disease-detection-app-rolled-out-for-farmers', 'AI-powered crop disease detection smartphone app rolled out for field agronomists', 'Machine learning visual diagnostics identify plant pests and nutrient deficiencies from a single leaf photo.', 'Science', 'news-rice.png', 4, 1680, 4190, false, [
                'An artificial intelligence mobile application trained on hundreds of thousands of local crop pathology images has been released to extension officers.',
                'Farmers can photograph affected paddy, jute, or vegetable leaves to receive instant, localized treatment recommendations in Bengali.',
                'Field trials demonstrated significant reductions in unnecessary pesticide usage and faster recovery for affected harvest plots.',
            ]],

            // --- Environment ---
            ['freshwater-dolphin-sanctuaries-see-encouraging-population-rise', 'Freshwater dolphin sanctuaries in Padma and Jamuna rivers report encouraging population rise', 'Community-patrolled river reserves, ban on destructive gillnets and cleaner river currents support species recovery.', 'Environment', 'watch-river.png', 4, 260, 4870, true, [
                'Wildlife conservationists have recorded a steady increase in sightings of the endangered Ganges river dolphin across protected river sanctuaries.',
                'Local fishing communities collaborated with wildlife rangers to safeguard deep river pools and rescue dolphins accidentally caught in nets.',
                'Biologists highlighted that a healthy dolphin population reflects improving aquatic biodiversity and river ecosystem vitality.',
            ]],
            ['plastic-waste-reduction-initiative-cleans-major-urban-canals', 'Urban canal cleanup and circular recycling initiative clears tons of plastic waste', 'Youth volunteer brigades and municipal workers restore vital storm-water flow channels across metropolitan canals.', 'Environment', 'news-coast.png', 4, 940, 3180, false, [
                'A citywide environmental drive has successfully removed accumulated plastic debris and silt from historic municipal drainage canals.',
                'Collected plastic waste is sorted and delivered to circular recycling plants that convert discarded polymers into durable industrial paving tiles.',
                'City authorities installed floating barrier screens and trash-capture booms to prevent further solid waste accumulation in urban waterways.',
            ]],
            ['community-forest-guard-groups-awarded-for-conservation-efforts', 'Community forest guard groups honored for safeguarding tropical evergreen reserves', 'Indigenous village patrols successfully protect biodiversity, old-growth timber and wildlife corridors from illegal logging.', 'Environment', 'news-rice.png', 4, 1740, 2430, false, [
                'Forest department officials presented national conservation honors to community co-management committees in the Lawachara and Satchari forests.',
                'Village patrol teams maintain round-the-clock foot vigils to prevent illegal tree felling and protect habitats of rare primates and birds.',
                'Revenue from eco-tourism guide fees and community nurseries is reinvested into village development projects and school scholarships.',
            ]],

            // --- Media ---
            ['bangladesh-betar-celebrates-milestone-with-next-gen-audio-portal', 'Bangladesh Betar celebrates major broadcast milestone with next-generation digital audio portal', 'Modern streaming apps, live radio channels, podcast series and rich news portal connect millions of global listeners.', 'Media', 'watch-hero.png', 4, 95, 8920, true, [
                'Bangladesh Betar has unveiled its state-of-the-art public digital platform, bringing national audio heritage and round-the-clock live radio to modern devices.',
                'The platform offers crystal-clear digital live broadcasts, an extensive music and drama archive, and a comprehensive public interest newsroom.',
                'Listeners from across the country and the global diaspora praised the intuitive interface, bilingual accessibility, and rich cultural catalogue.',
            ]],
            ['fact-checking-network-partners-with-rural-community-radio', 'National fact-checking network partners with rural community radio stations', 'Collaborative broadcast segments debunk online misinformation, health rumors and financial scams for grassroots listeners.', 'Media', 'news-tech.png', 4, 560, 3670, false, [
                'Journalism organizations and community radio broadcasters have launched a joint initiative to combat digital disinformation in rural communities.',
                'Weekly radio magazines verify viral social media claims, clarify official government announcements, and offer digital literacy tips.',
                'Community leaders reported that the trusted audio broadcasts help protect vulnerable citizens from falling prey to fraudulent online schemes.',
            ]],
            ['journalism-fellowships-awarded-for-investigative-climate-reporting', 'Prestigious journalism fellowships awarded for investigative climate reporting', 'Reporters from print, television and digital newsrooms receive grants to investigate groundwater, coastal erosion and green transition.', 'Media', 'news-coast.png', 4, 1380, 2890, false, [
                'The National Press Institute has awarded competitive investigative reporting fellowships to twelve outstanding young journalists.',
                'Fellows will undertake multi-month field investigations into groundwater salinity in the southwest delta and renewable energy transitions in the north.',
                'Editorial mentors from leading media organizations will provide technical guidance in data journalism, multimedia storytelling and ethical reporting.',
            ]],
        ];

        $newsBn = [
            'rail-link-connects-river-regions' => ['নতুন রেলপথে নদীবেষ্টিত অঞ্চলের সঙ্গে রাজধানীর যোগাযোগ সহজ', 'সম্প্রসারিত রুটটি যাত্রার সময় কমাবে, আঞ্চলিক বাণিজ্য বাড়াবে এবং আরও যাত্রীকে নির্ভরযোগ্য গণপরিবহনের আওতায় আনবে।', [
                'দেশের ব্যস্ততম নদী করিডরের একটি দিয়ে নতুন সম্প্রসারিত রেল যোগাযোগে যাত্রী পরিবহন শুরু হয়েছে। এতে আঞ্চলিক শহরগুলোর সঙ্গে ঢাকার দ্রুত যোগাযোগ তৈরি হয়েছে।',
                'পরিবহন পরিকল্পনাবিদরা বলছেন, সড়কের চাপ কমানোর পাশাপাশি শিক্ষা, স্বাস্থ্যসেবা ও বাজারে যাতায়াত সহজ করতেই এই সেবা চালু হয়েছে।',
                'স্থানীয় ব্যবসায়ীরা আশা করছেন, নির্ভরযোগ্য যাত্রাসময় জেলার মধ্যে কৃষিপণ্য ও ক্ষুদ্র শিল্পপণ্য পরিবহন সহজ করবে।',
            ]],
            'coastal-volunteers-complete-shelter-drill' => ['উপকূলীয় স্বেচ্ছাসেবকদের আশ্রয়কেন্দ্র মহড়া সম্পন্ন', 'দুর্যোগ মৌসুমের আগে প্রাথমিক চিকিৎসা, সরিয়ে নেওয়ার পথ ও যোগাযোগব্যবস্থা পরীক্ষা করেছে স্থানীয় দলগুলো।', [
                'উপকূলীয় স্বেচ্ছাসেবকেরা ঘূর্ণিঝড় আশ্রয়কেন্দ্রে পৌঁছানো ও পরিবারের সঙ্গে যোগাযোগের প্রস্তুতি নিয়ে সমন্বিত মহড়া শেষ করেছেন।',
                'মহড়ায় বয়স্ক, শিশু ও প্রতিবন্ধী মানুষকে সরিয়ে নেওয়ার অনুশীলন এবং জরুরি সরঞ্জাম পরীক্ষা করা হয়।',
                'ভারী বৃষ্টিতে যেসব প্রত্যন্ত এলাকায় চলাচল কঠিন হয়, সেখানেও এই মহড়া আয়োজন করা হবে।',
            ]],
            'dhaka-metro-expands-evening-schedule' => ['যাত্রীদের সুবিধার্থে ঢাকা মেট্রোর সান্ধ্যকালীন সময়সূচি সম্প্রসারণ', 'অতিরিক্ত ট্রেন চলাচল এবং প্রান্তিক স্টেশনের বর্ধিত সময় ব্যস্ত সময়ে নগরবাসীর স্বস্তি নিশ্চিত করেছে।', [
                'বাণিজ্যিক এলাকা থেকে ফেরা যাত্রীদের ক্রমবর্ধমান চাপ সামলাতে ঢাকা ম্যাস ট্রানজিট কর্তৃপক্ষ সান্ধ্যকালীন ট্রেনের সংখ্যা বাড়িয়েছে।',
                'যাত্রীরা জানান, এর ফলে স্টেশনে অপেক্ষার সময় উল্লেখযোগ্যভাবে কমেছে এবং গন্তব্যে পৌঁছানো অনেক সহজ হয়েছে।',
                'মেট্রো স্টেশনের সঙ্গে মিল রেখে সংযোগকারী বাস রুটের সমন্বয় করা হচ্ছে যাতে শেষ প্রান্ত পর্যন্ত যাতায়াত নির্বিঘ্ন থাকে।',
            ]],
            'sylhet-tea-estates-adopt-solar-irrigation' => ['সিলেটের চা বাগানে সৌরবিদ্যুৎচালিত সেচ প্রযুক্তির ব্যবহার', 'পাহাড়ি পরিবেশ রক্ষা করে শুষ্ক মৌসুমেও উন্নতমানের চা উৎপাদন বজায় রাখছে নতুন প্রযুক্তি।', [
                'সিলেট অঞ্চলের বেশ কয়েকটি ঐতিহ্যবাহী চা বাগান তাদের পাহাড়ি ঢালে সৌরচালিত মাইক্রো-স্প্রিংকলার সেচ নেটওয়ার্ক স্থাপন করেছে।',
                'বাগান ব্যবস্থাপকেরা জানিয়েছেন, নিয়মিত আর্দ্রতা বজায় থাকায় সাম্প্রতিক খরায় পাতার ক্ষতি রোধ করা সম্ভব হয়েছে।',
                'এই উদ্যোগ ডিজেল পাম্পের ওপর নির্ভরতা কমিয়েছে, ফলে কার্বন নিঃসরণ এবং স্থানীয় পাহাড়ি ছড়ার দূষণ রোধ হচ্ছে।',
            ]],
            'chattogram-port-sets-record-container-throughput' => ['ডিজিটাল রূপান্তরে চট্টগ্রাম বন্দরে কনটেইনার হ্যান্ডলিংয়ে নতুন রেকর্ড', 'স্বয়ংক্রিয় গেট পাস ও স্মার্ট ইয়ার্ড ট্র্যাকিংয়ে জাহাজের অবস্থানকাল বিশ শতাংশ কমেছে।', [
                'স্বয়ংক্রিয় টার্মিনাল ব্যবস্থাপনা সফটওয়্যার চালুর পর চট্টগ্রাম বন্দর কর্তৃপক্ষ পণ্য পরিবহনে ঐতিহাসিক রেকর্ড গড়েছে।',
                'আমদানিকারক ও শিপিং কোম্পানিগুলো নিরবচ্ছিন্ন কাস্টমস ছাড়করণ ও সার্বক্ষণিক সেবার প্রশংসা করেছে।',
                'বন্দর কর্তৃপক্ষ জানিয়েছে, আগামী প্রজন্মের বড় কনটেইনার জাহাজ ভেড়ানোর জন্য গভীর ড্রাফটের বার্থ নির্মাণ কার্যক্রম চলমান রয়েছে।',
            ]],

            'parliament-passes-digital-public-service-reform-bill' => ['ডিজিটাল নাগরিক সেবা নিশ্চিতকরণ বিল জাতীয় সংসদে পাস', 'নতুন আইনে নির্দিষ্ট সময়ে নাগরিক সেবা প্রদান, স্বয়ংক্রিয় অভিযোগ নিষ্পত্তি ও উন্মুক্ত তথ্য ব্যবস্থা চালু হচ্ছে।', [
                'সরকারি নাগরিক সেবা ডিজিটাল ও সহজতর করার লক্ষ্যে জাতীয় সংসদে যুগান্তকারী একটি বিল সর্বসম্মতিক্রমে পাস হয়েছে।',
                'নতুন কাঠামোর আওতায় জন্মনিবন্ধন, জমির খতিয়ান ও নাগরিক সনদ প্রদানে সুনির্দিষ্ট সময়সীমা মানা বাধ্যতামূলক করা হয়েছে।',
                'সংসদ সদস্যরা ব্যক্তিগত তথ্যের নিরাপত্তা বিধান এবং স্বচ্ছ জবাবদিহিতা নিশ্চিত করার বিষয়টিকে স্বাগত জানিয়েছেন।',
            ]],
            'election-commission-upgrades-biometric-voter-verification' => ['নির্বাচন কমিশনের আধুনিক বায়োমেট্রিক ভোটার শনাক্তকরণ প্রযুক্তি', 'আসন্ন নির্বাচনে নির্ভুল, দ্রুত ও নিরাপদ ভোটগ্রহণ নিশ্চিত করতে আধুনিক ডিভাইস প্রস্তুত করা হয়েছে।', [
                'নির্বাচন কমিশন মাঠপর্যায়ে দ্রুত ভোটার যাচাইয়ের জন্য আধুনিক বায়োমেট্রিক কিট উন্মোচন করেছে।',
                'নতুন পোর্টেবল ডিভাইসগুলোতে উন্নত এনক্রিপশন, উচ্চ রেজল্যুশনের আঙুলের ছাপ সেন্সর এবং দীর্ঘস্থায়ী ব্যাটারি রয়েছে।',
                'নাগরিক প্রতিনিধি ও রাজনৈতিক দলগুলোর উপস্থিতিতে বিভাগীয় পর্যায়ে সফলভাবে এর কার্যকারিতা প্রদর্শন করা হয়।',
            ]],
            'local-government-dialogue-focuses-on-civic-transparency' => ['স্থানীয় সরকার সংলাপে অংশীদারত্বমূলক বাজেট স্বচ্ছতার ওপর জোর', 'ইউনিয়ন পরিষদ ও নাগরিক প্রতিনিধিদের অংশগ্রহণে তৃণমূলের উন্নয়ন পরিকল্পনার পর্যালোচনা।', [
                'তৃণমূল প্রশাসনে নাগরিক অংশগ্রহণ নিশ্চিত করতে সারা দেশের পৌর মেয়র ও ইউপি চেয়ারম্যানদের নিয়ে জাতীয় সংলাপ অনুষ্ঠিত হয়েছে।',
                'গ্রামাঞ্চলের সড়ক, ড্রেনেজ ও সড়কবাতি স্থাপনে জনগণের উন্মুক্ত মতামতের ভিত্তিতে বাজেট বরাদ্দের উদাহরণ তুলে ধরা হয়।',
                'মন্ত্রণালয়ের পক্ষ থেকে জানানো হয়েছে, বার্ষিক উন্নয়ন সহায়তা তহবিলের বরাদ্দ নাগরিক সম্পৃক্ততার ওপর ভিত্তি করে নির্ধারণ করা হবে।',
            ]],
            'all-party-climate-caucus-calls-for-delta-funding' => ['ডেল্টা সুরক্ষায় জলবায়ু তহবিলের অনুদান বাড়ানোর দাবি সর্বদলীয় ফোরামের', 'নদীভাঙন ও উপকূল রক্ষায় ঋণের পরিবর্তে সরাসরি আন্তর্জাতিক জলবায়ু অনুদান নিশ্চিতের আহ্বান।', [
                'নদীর বাঁধ সুরক্ষা ও উপকূলীয় জনগোষ্ঠীর পুনর্বাসনে আন্তর্জাতিক অনুদান বৃদ্ধির আহ্বান জানিয়েছে সংসদীয় জলবায়ু ককাস।',
                'যৌথ ঘোষণায় অভিযোজন পরিকল্পনায় বাংলাদেশের অগ্রণী ভূমিকার কথা স্মরণ করিয়ে দিয়ে দুর্যোগপ্রবণ এলাকা রক্ষায় তহবিল দ্রুত ছাড়ের দাবি জানানো হয়।',
                'আসন্ন আন্তর্জাতিক জলবায়ু অর্থায়ন গোলটেবিল বৈঠকে এই প্রস্তাবনা তুলে ধরবেন সংসদীয় প্রতিনিধিদল।',
            ]],

            'aman-harvest-reaches-local-markets' => ['আমনের ভালো ফলন স্থানীয় বাজারে পৌঁছাতে শুরু করেছে', 'সেচ ব্যবস্থাপনার সুফলে উত্তরের কয়েকটি জেলার কৃষকেরা ভালো ফলনের কথা জানিয়েছেন।', [
                'উত্তরের জেলাগুলোতে আমন ধান কাটা শেষ হওয়ার সঙ্গে সঙ্গে নতুন চাল আঞ্চলিক বাজারে আসছে।',
                'কৃষি কর্মকর্তারা জানান, স্থানীয় সেচ পরিকল্পনা ও সময়মতো পরামর্শ পরিবর্তনশীল আবহাওয়ায় ফসল রক্ষায় সহায়তা করেছে।',
                'খামার থেকে মিল ও খুচরা বাজারে ধান পৌঁছানোর সময় পরিবহন ও সংরক্ষণ ব্যয় পর্যবেক্ষণ করা হচ্ছে।',
            ]],
            'central-bank-eases-remittance-transfer-protocols' => ['প্রবাসীদের রেমিট্যান্স প্রেরণে সহজ ডিজিটাল সুবিধা চালু করল কেন্দ্রীয় ব্যাংক', 'বৈধ চ্যানেলে তাৎক্ষণিক প্রণোদনা ও নামমাত্র খরচে প্রবাসীদের কষ্টার্জিত অর্থ দেশে পাঠানোর সুযোগ।', [
                'প্রবাসী কর্মীদের জন্য সহজ শর্তে ডিজিটাল ওয়ালেটের মাধ্যমে রেমিট্যান্স পাঠানোর নতুন নীতিমালা জারি করেছে বাংলাদেশ ব্যাংক।',
                'যাচাইকৃত ব্যাংকিং অ্যাপ ব্যবহারে সরকারি নগদ প্রণোদনা কোনো মধ্যস্বত্বভোগী ছাড়াই সরাসরি ব্যাংক অ্যাকাউন্টে জমা হবে।',
                'বাণিজ্যিক ব্যাংকগুলো আশা করছে, এই পদক্ষেপের ফলে ব্যাংকিং চ্যানেলে বৈদেশিক মুদ্রার প্রবাহ উল্লেখযোগ্য হারে বাড়বে।',
            ]],
            'leather-and-footwear-exports-post-double-digit-growth' => ['চামড়া ও জুতা রপ্তানিতে দুই অঙ্কের প্রবৃদ্ধি অর্জিত', 'পরিবেশবান্ধব ট্যানারি কমপ্লায়েন্স ও আন্তর্জাতিক সার্টিফিকেশনের ফলে ইউরোপ-এশিয়ায় নতুন বাজার সৃষ্টি।', [
                'রপ্তানি উন্নয়ন ব্যুরোর হালনাগাদ তথ্যে দেখা গেছে, চামড়াজাত পণ্য ও জুতা রপ্তানিতে উল্লেখযোগ্য প্রবৃদ্ধি হয়েছে।',
                'শিল্প উদ্যোক্তারা জানান, কেন্দ্রীয় বর্জ্য শোধনাগারের আধুনিকায়ন ও টেকসই উৎপাদন পদ্ধতির কারণে আন্তর্জাতিক ক্রেতাদের আস্থা বাড়ছে।',
                'বিশ্বমানের পণ্য তৈরিতে দেশীয় ডিজাইনার ও কারিগরদের জন্য বিশেষ প্রশিক্ষণ একাডেমি চালু করা হচ্ছে।',
            ]],
            'mobile-financial-services-reach-remote-char-islands' => ['যমুনার দুর্গম চরাঞ্চলে পৌঁছেছে এজেন্ট ব্যাংকিং ও মোবাইল ফিন্যান্সিয়াল সার্ভিস', 'সৌরবিদ্যুৎচালিত ডিজিটাল পয়েন্টের মাধ্যমে চরের মানুষ পাচ্ছেন সঞ্চয়, কৃষিঋণ ও ভাতা প্রাপ্তির সুবিধা।', [
                'পদ্মা ও যমুনা নদীর দুর্গম চরাঞ্চলে আর্থিক সেবা পৌঁছে দিতে বিশেষ এজেন্ট ব্যাংকিং কিয়স্ক চালু করা হয়েছে।',
                'চরের কৃষকেরা এখন দীর্ঘ ও ঝুঁকিপূর্ণ নৌভ্রমণ ছাড়াই সরাসরি সরকারি কৃষিভাতা ও রেমিট্যান্সের অর্থ গ্রহণ করছেন।',
                'স্থানীয় নারীদের জন্য আয়োজিত আর্থিক সাক্ষরতা কর্মশালার মাধ্যমে ক্ষুদ্র ব্যবসার সহজ শর্তের ঋণ প্রদান করা হচ্ছে।',
            ]],
            'startups-secure-regional-seed-investments-in-agritech' => ['আন্তর্জাতিক বিনিয়োগ পেল দেশের দুই কৃষিপ্রযুক্তি স্টার্টআপ', 'কৃষকদের ডেটাভিত্তিক পরামর্শ ও কোল্ড-চেইন সরবরাহ নিশ্চিতে তৈরি অ্যাপে আস্থা রাখছেন বিদেশি বিনিয়োগকারীরা।', [
                'বাংলাদেশের দুটি সম্ভাবনাময় এগ্রিটেক স্টার্টআপ আঞ্চলিক বিনিয়োগ তহবিল থেকে সিড রাউন্ডের মূলধন সংগ্রহ করেছে।',
                'তাদের প্ল্যাটফর্ম স্যাটেলাইট চিত্র ও মাটির সেন্সর বিশ্লেষণ করে কৃষকদের সুনির্দিষ্ট সার ও সেচের পরামর্শ দেয়।',
                'বিনিয়োগকারীরা বলছেন, ডেল্টা অঞ্চলে কৃষি আধুনিকায়নে তথ্যপ্রযুক্তির ব্যবহার দারুণ অর্থনৈতিক সম্ভাবনা তৈরি করেছে।',
            ]],

            'river-research-maps-seasonal-change' => ['মৌসুমি নদীর পরিবর্তন মানচিত্রে তুলে ধরছেন গবেষকেরা', 'উপগ্রহ পর্যবেক্ষণ ও নদীপারের মানুষের তথ্য মিলিয়ে একটি নতুন উন্মুক্ত উপাত্তভান্ডার তৈরি হয়েছে।', [
                'গবেষকেরা মৌসুমভেদে নদীর গতিপথ ও আশপাশের বসতি কীভাবে বদলায় তার উন্মুক্ত উপাত্ত প্রকাশ করেছেন।',
                'প্রকল্পটিতে উপগ্রহচিত্রের সঙ্গে স্কুল ও স্থানীয় সংগঠনের পর্যবেক্ষণ যুক্ত করা হয়েছে।',
                'এই তথ্য ভাঙনপ্রবণ এলাকায় নিরাপদ অবকাঠামো ও উন্নত পরিকল্পনায় সহায়তা করবে বলে আশা করা হচ্ছে।',
            ]],
            'un-climate-summit-agrees-on-new-loss-and-damage-facility' => ['জাতিসংঘ জলবায়ু সম্মেলনে লস অ্যান্ড ড্যামেজ তহবিলের নীতিমালায় সম্মতি', 'ঝুঁকিপূর্ণ ডেল্টা ও দ্বীপরাষ্ট্রগুলোর জরুরি পুনর্বাসনে বিশেষ আর্থিক সুবিধার দ্বার উন্মোচিত।', [
                'আন্তর্জাতিক জলবায়ু সম্মেলনে ক্ষতিগ্রস্ত দেশগুলোর দুর্যোগ পরবর্তী সহায়তার জন্য লস অ্যান্ড ড্যামেজ ফান্ডের নিয়মাবলি চূড়ান্ত হয়েছে।',
                'এই কাঠামোর মাধ্যমে চরম আবহাওয়াজনিত ক্ষয়ক্ষতি ও সমুদ্রপৃষ্ঠের উচ্চতা বৃদ্ধিতে ক্ষতিগ্রস্ত এলাকায় দ্রুত অর্থ পৌঁছানো সম্ভব হবে।',
                'স্বল্পোন্নত ও জলবায়ু ঝুঁকিপূর্ণ দেশগুলোর পক্ষে বাংলাদেশের বলিষ্ঠ নেতৃত্বের প্রশংসা করেছেন আন্তর্জাতিক প্রতিনিধিরা।',
            ]],
            'bangladesh-japan-trade-pact-enters-final-review' => ['বাংলাদেশ-জাপান অর্থনৈতিক অংশীদারত্ব চুক্তি চূড়ান্ত পর্যালোচনায়', 'শুল্কমুক্ত রপ্তানি, প্রযুক্তিগত সহযোগিতা ও দক্ষ জনশক্তি বিনিময় নিয়ে আলোচনা শেষ পর্যায়ে।', [
                'টোকিও ও ঢাকায় দ্বিপক্ষীয় মুক্তবাণিজ্য ও অর্থনৈতিক অংশীদারত্ব চুক্তির খসড়া নিয়ে চূড়ান্ত আলোচনা সম্পন্ন হয়েছে।',
                'চুক্তির ফলে শিল্পোৎপাদন, অটোমোবাইল প্রযুক্তি ও নবায়নযোগ্য শক্তিতে জাপানি বিনিয়োগের অপার সুযোগ সৃষ্টি হবে।',
                'উভয় দেশের শীর্ষ ব্যবসায়িক প্রতিনিধিরা চুক্তিটি দ্রুত স্বাক্ষরের মাধ্যমে দ্বিপক্ষীয় বাণিজ্য সম্প্রসারণের প্রত্যাশা করছেন।',
            ]],
            'south-asian-regional-power-grid-trials-begin' => ['দক্ষিণ এশিয়ায় আঞ্চলিক পরিবেশবান্ধব বিদ্যুৎ সঞ্চালন গ্রিডের সফল পরীক্ষা', 'হিমালয়ের জলবিদ্যুৎ ও উপকূলীয় বিদ্যুতের মৌসুমভিত্তিক আদান-প্রদানে নতুন দিগন্ত।', [
                'আঞ্চলিক উচ্চক্ষমতাসম্পন্ন ক্রস-বর্ডার গ্রিড লাইনে সফলভাবে পরীক্ষামূলক বিদ্যুৎ সঞ্চালন শুরু হয়েছে।',
                'এই উদ্যোগের ফলে গ্রীষ্মকালে নেপাল ও ভুটানের উদ্বৃত্ত জলবিদ্যুৎ বাংলাদেশের শিল্পাঞ্চলের চাহিদা মেটাতে ব্যবহৃত হবে।',
                'জ্বালানি বিশেষজ্ঞরা বলছেন, এই সংযোগ দক্ষিণ এশিয়ার সার্বিক কার্বন নিঃসরণ হ্রাস এবং টেকসই জ্বালানি নিরাপত্তায় বড় মাইলফলক।',
            ]],

            'bangladesh-clinches-thrilling-t20-series-decider' => ['মিরপুরে শ্বাসরুদ্ধকর শেষ ওভারে সিরিজ জয় করল বাংলাদেশ', 'ডেথ ওভারে দুর্দান্ত বোলিং ও মিডল অর্ডারের দায়িত্বশীল ব্যাটিংয়ে স্মরণীয় জয়।', [
                'মিরপুর শের-ই-বাংলা জাতীয় ক্রিকেট স্টেডিয়ামে দর্শকদের উল্লাসে ভাসিয়ে সিরিজের শেষ ম্যাচে রুদ্ধশ্বাস জয় তুলে নিয়েছে টাইগাররা।',
                'শেষ ওভারগুলোতে বোলারদের নিখুঁত লাইন-লেংথ ও বুদ্ধিদীপ্ত স্লোয়ার সফরকারী দলকে আটকে দেয়।',
                'অধিনায়ক তরুণ ক্রিকেটারদের চাপের মুখে শান্ত থাকা এবং দলীয় পরিকল্পনা বাস্তবায়নের ভূয়সী প্রশংসা করেন।',
            ]],
            'national-women-football-team-qualifies-for-asian-cup-stage' => ['ইতিহাস গড়ে এশিয়ান কাপের নকআউট পর্বে বাংলাদেশ নারী ফুটবল দল', 'চমৎকার আক্রমণাত্মক খেলা এবং দৃঢ় রক্ষণভাগে গ্রুপ পর্বে অপরাজিত থাকার গৌরব।', [
                'অপরাজিত থেকে আন্তর্জাতিক টুর্নামেন্টের নকআউট পর্বে স্থান করে নিয়ে ইতিহাস রচনা করেছে বাংলাদেশের মেয়েরা।',
                '৯০ মিনিটের টানটান উত্তেজনার ম্যাচে দুর্দান্ত উইং প্লে এবং গোলরক্ষকের বীরত্বপূর্ণ সেভ দলকে অবিস্মরণীয় জয় এনে দেয়।',
                'দেশজুড়ে সামাজিক যোগাযোগমাধ্যম ও রাজপথে নারী ফুটবলারদের এই অভাবনীয় সাফল্য উদযাপিত হচ্ছে।',
            ]],
            'youth-archery-contingent-claims-gold-at-asiad-qualifiers' => ['এশিয়ান যুব আর্চারিতে বাংলাদেশের সোনা জয়', 'রিকার্ভ ও কম্পাউন্ড ইভেন্টে অসাধারণ লক্ষ্যভেদে জোড়া স্বর্ণপদক অর্জন।', [
                'আন্তর্জাতিক আসরে চোখধাঁধানো নৈপুণ্য প্রদর্শন করে রিকার্ভ মিশ্র ও একক ইভেন্টে জোড়া সোনা জিতেছে বাংলাদেশের তরুণ আর্চাররা।',
                'কোচেরা জানিয়েছেন, ক্রীড়া মনস্তত্ত্ব ও আধুনিক সিমুলেটরে অনুশীলনের ফলে খেলোয়াড়দের আত্মবিশ্বাস বহুগুণ বৃদ্ধি পেয়েছে।',
                'জাতীয় আর্চারি ফেডারেশন জানিয়েছে, পদকজয়ী আর্চারদের বিশ্ব যুব চ্যাম্পিয়নশিপের জন্য বিশেষ নিবিড় ক্যাম্পে রাখা হবে।',
            ]],
            'district-kabaddi-championship-draws-huge-crowds-in-bogura' => ['বগুড়ায় ঐতিহ্যবাহী জেলা কাবাডি প্রতিযোগিতায় দর্শকের ঢল', 'গ্রামাঞ্চলের ঐতিহ্যবাহী খেলায় অংশ নিচ্ছে তরুণ ও অভিজ্ঞ খেলোয়াড়দের একাধিক দল।', [
                'ঐতিহ্যবাহী কাবাডি টুর্নামেন্টের ফাইনাল উপভোগ করতে বগুড়ার খেলার মাঠে দূর-দূরান্ত থেকে হাজারো ক্রীড়াপ্রেমী মানুষ সমবেত হন।',
                'চমকপ্রদ রেইড ও ক্ষিপ্রগতির ট্যাকল খেলায় বাড়তি উত্তেজনা ছড়ায়।',
                'আয়োজকেরা জানান, গ্রামীণ ঐতিহ্যবাহী খেলাধুলা বাঁচিয়ে রাখা তরুণ সমাজকে সুস্থ ও মাদকমুক্ত রাখতে গুরুত্বপূর্ণ ভূমিকা রাখছে।',
            ]],

            'indie-film-on-sundarbans-wins-international-festival-acclaim' => ['সুন্দরবনের জীবনগাথা নিয়ে নির্মিত চলচ্চিত্র আন্তর্জাতিক উৎসবে পুরস্কৃত', 'মৌয়াল ও জেলেদের সংগ্রাম এবং বনের অপরূপ প্রকৃতির দৃশ্য আন্তর্জাতিক মহলে প্রশংসিত।', [
                'সুন্দরবনের বাদাবন ও জলপথের পটভূমিতে নির্মিত একটি দেশীয় চলচ্চিত্র নামকরা আন্তর্জাতিক চলচ্চিত্র উৎসবে সেরা ছবির পুরস্কার জিতেছে।',
                'স্থানীয় বনজীবীদের অভিনয়, প্রাকৃতিক রূপ ও নিখুঁত সাউন্ড ডিজাইনের ভূয়সী প্রশংসা করেছেন আন্তর্জাতিক সমালোচকরা।',
                'চলচ্চিত্র নির্মাতা দল জানিয়েছে, শিগগিরই দেশের বিভিন্ন বিশ্ববিদ্যালয় ও প্রেক্ষাগৃহে সিনেমাটি প্রদর্শিত হবে।',
            ]],
            'betar-golden-era-musical-archive-digitized-for-streaming' => ['বেতারের সোনালী যুগের সংগীত সম্ভার সংরক্ষিত হচ্ছে ডিজিটাল স্ট্রিমিংয়ে', 'ষাটের ও সত্তরের দশকের কিংবদন্তি শিল্পীদের মাস্টার টেপ আধুনিক প্রযুক্তিতে ডিজিটালাইজড।', [
                'বাংলাদেশ বেতার তাদের আর্কাইভে সংরক্ষিত হাজারো ঐতিহাসিক গান ও বেতার নাটকের মাস্টার টেপ আধুনিক প্রযুক্তিতে রিমাস্টারিং সম্পন্ন করেছে।',
                'শ্রোতারা এখন যেকোনো ডিজিটাল মাধ্যমে কিংবদন্তি শিল্পীদের কালজয়ী কণ্ঠ ও শাস্ত্রীয় সংগীত শুনতে পারবেন।',
                'গবেষকরা বলছেন, এই উদ্যোগ ভবিষ্যৎ প্রজন্মের জন্য আমাদের সমৃদ্ধ সাংস্কৃতিক ঐতিহ্যকে সুরক্ষিত রাখবে।',
            ]],
            'new-period-drama-series-depicts-1952-language-movement' => ['বায়ান্নর ভাষা আন্দোলনের আত্মত্যাগ নিয়ে তৈরি নতুন ধারাবাহিক নাটক', 'তৎকালীন ছাত্রসমাজের সংগ্রামী ইতিহাস ও সাংস্কৃতিক জাগরণ তুলে ধরা হয়েছে পর্দায়।', [
                '১৯৫২ সালের মহান ভাষা আন্দোলনের ঐতিহাসিক প্রেক্ষাপটে নির্মিত নতুন একটি ধারাবাহিক নাটক দর্শকের প্রশংসা কুড়াচ্ছে।',
                'ছাত্রদের রক্তক্ষয়ী সংগ্রাম, সাহিত্যিকদের গোপন প্রকাশনা ও তৎকালীন সমাজের আবেগময় চিত্র চমৎকারভাবে ফুটিয়ে তোলা হয়েছে।',
                'শিক্ষাবিদ ও সংস্কৃতিকর্মীরা তরুণ প্রজন্মকে সঠিক ইতিহাস জানানোর এই উদ্যোগের প্রশংসা করেছেন।',
            ]],
            'dhaka-theatre-festival-showcases-young-playwrights' => ['ঢাকা নাট্যোৎসবে তরুণ নাট্যকারদের মঞ্চনাটক উপভোগ করছেন দর্শক', 'সমসাময়িক সমাজ ও তরুণ প্রজন্মের আত্মানুসন্ধান নিয়ে মঞ্চস্থ হচ্ছে নতুন নতুন নাটক।', [
                'রাজধানীতে আয়োজিত জাতীয় নাট্যোৎসবে নবীন নাট্যকার ও নির্দেশকদের মৌলিক নাটক দেখতে মিলনায়তনগুলোতে দর্শকের ভিড় জমছে।',
                'নব্য ধারার মঞ্চসজ্জা ও দেশীয় বাদ্যযন্ত্রের সংমিশ্রণে প্রতিটি নাটক দর্শকের ভূয়সী প্রশংসা অর্জন করছে।',
                'উৎসব কর্তৃপক্ষ সেরা নাটকগুলোকে দেশের বিভিন্ন জেলা শহরে মঞ্চায়নের জন্য বিশেষ ফেলোশিপ ঘোষণা করেছে।',
            ]],

            'bcs-recruitment-circular-announces-new-technical-cadres' => ['বিসিএসে নতুন কারিগরি ক্যাডার যুক্ত করে নিয়োগ বিজ্ঞপ্তি প্রকাশ', 'ডেটা অ্যানালিটিক্স, পরিবেশ বিজ্ঞান ও সাইবার নিরাপত্তায় দক্ষ তরুণদের প্রশাসনে সুযোগ।', [
                'পাবলিক সার্ভিস কমিশন তাদের নতুন নিয়োগ বিজ্ঞপ্তিতে প্রযুক্তিনির্ভর বেশ কয়েকটি নতুন কারিগরি ক্যাডার পদ অন্তর্ভুক্ত করেছে।',
                'তথ্যপ্রযুক্তি, পরিবেশ বিজ্ঞান ও পরিসংখ্যানের স্নাতকেরা এসব পদে সরাসরি আবেদনের সুযোগ পাবেন।',
                'কমিশন নিশ্চিত করেছে, সম্পূর্ণ মেধা ও স্বচ্ছতার ভিত্তিতে প্রিলিমিনারি ও মৌখিক পরীক্ষা অনুষ্ঠিত হবে।',
            ]],
            'it-freelancing-skill-hubs-launched-in-twenty-districts' => ['২০ জেলায় চালু হলো আইটি ফ্রিল্যান্সিং ও আধুনিক স্কিল হাব', 'বিশ্ববাজারে রিমোট কাজের সুযোগ তৈরি করতে গ্রামীণ তরুণদের দেয়া হচ্ছে বিনা মূল্যে প্রশিক্ষণ।', [
                'তথ্যপ্রযুক্তি বিভাগ সারা দেশের ২০টি জেলায় আধুনিক ফ্রিল্যান্সিং ও সফটওয়্যার প্রশিক্ষণ হাব চালু করেছে।',
                'প্রশিক্ষণার্থীরা আন্তর্জাতিক মার্কেটপ্লেসে কাজ পাওয়ার জন্য প্রয়োজনীয় কোডিং, গ্রাফিক্স ও ডিজিটাল মার্কেটিং শিখছেন।',
                'প্রথম ব্যাচের সফল ফ্রিল্যান্সাররা ইতিমধ্যেই ব্যাংকিং চ্যানেলে বৈদেশিক মুদ্রা আয় করতে শুরু করেছেন।',
            ]],
            'polytechnic-graduates-gain-fast-track-industrial-apprenticeships' => ['পলিটেকনিক শিক্ষার্থীদের জন্য শিল্প কারখানায় পেইড ইন্টার্নশিপের সুযোগ', 'সরকারি-বেসরকারি অংশীদারত্বে ডিপ্লোমা প্রকৌশলীদের সরাসরি কারখানায় চাকরির ব্যবস্থা।', [
                'পলিটেকনিক থেকে উত্তীর্ণ ডিপ্লোমা ইঞ্জিনিয়ারদের জন্য দেশের শীর্ষ শিল্প কারখানাগুলোতে ছয় মাসের ইন্টার্নশিপ চুক্তি স্বাক্ষরিত হয়েছে।',
                'শিক্ষার্থীরা আধুনিক কারখানা ফ্লোরে অটোমেশন ও রোবটিক্স রক্ষণাবেক্ষণের বাস্তব অভিজ্ঞতা অর্জন করবেন।',
                'সংশ্লিষ্টরা জানান, এই উদ্যোগ দেশীয় শিল্পে দক্ষ জনবলের সংকট দূর করতে কার্যকর ভূমিকা রাখবে।',
            ]],
            'primary-teacher-recruitment-results-published-nationwide' => ['প্রাথমিক শিক্ষক নিয়োগ পরীক্ষার চূড়ান্ত মেধা তালিকা প্রকাশিত', 'গ্রামাঞ্চলের প্রাথমিক বিদ্যালয়ে শিক্ষার মান বাড়াতে সহস্রাধিক শিক্ষক পদায়ন।', [
                'প্রাথমিক শিক্ষা অধিদপ্তর সারা দেশের সরকারি প্রাথমিক বিদ্যালয়ের সহকারী শিক্ষক নিয়োগের চূড়ান্ত ফল প্রকাশ করেছে।',
                'সম্পূর্ণ স্বয়ংক্রিয় পদ্ধতিতে ওএমআর মূল্যায়ন এবং মেধা কোটার ভিত্তিতে এই তালিকা প্রস্তুত করা হয়।',
                'নতুন শিক্ষকেরা আধুনিক শিশুতোষ পাঠদান ও ডিজিটাল শ্রেণি ব্যবস্থাপনা বিষয়ে বিশেষ প্রশিক্ষণ গ্রহণ করবেন।',
            ]],

            'student-robotics-team-heads-to-regional-final' => ['শিক্ষার্থী রোবটিক্স দল আঞ্চলিক উদ্ভাবন প্রতিযোগিতার ফাইনালে', 'স্থানীয় উপকরণ ও উন্মুক্ত প্রযুক্তিতে তৈরি স্বল্পমূল্যের পরিদর্শন রোভার দলটিকে ফাইনালে নিয়েছে।', [
                'একটি শিক্ষার্থী প্রকৌশল দল সংকীর্ণ স্থান পরিদর্শনের জন্য ছোট রোভার তৈরি করে আঞ্চলিক উদ্ভাবন প্রতিযোগিতার ফাইনালে উঠেছে।',
                'স্বল্পমূল্যের সেন্সর ও স্থানীয় যন্ত্রাংশে তৈরি হওয়ায় বিশেষ সরঞ্জাম ছাড়াই রোভারটি মেরামত ও পরিবর্তন করা যায়।',
                'এই উদ্যোগ আরও স্কুল ও বিশ্ববিদ্যালয়কে ব্যবহারিক রোবটিক্স ক্লাব গড়তে উৎসাহিত করবে বলে দলটি আশা করছে।',
            ]],
            'community-health-clinics-introduce-telemedicine-counseling' => ['কমিউনিটি ক্লিনিকে টেলিমেডিসিন ও মানসিক স্বাস্থ্য পরামর্শ সেবা চালু', 'প্রত্যন্ত গ্রামের রোগীরা ভিডিও কনফারেন্সের মাধ্যমে বিশেষজ্ঞ চিকিৎসকের ব্যবস্থাপত্র পাচ্ছেন।', [
                'উত্তরাঞ্চলের বেশ কয়েকটি উপজেলার কমিউনিটি ক্লিনিকে বিশেষজ্ঞ চিকিৎসকদের ভিডিও কনফারেন্সিং সেবা চালু হয়েছে।',
                'গ্রামের রোগীরা নিখরচায় বিশেষজ্ঞ চিকিৎসকের পরামর্শ, প্রেসক্রিপশন এবং প্রয়োজনীয় মানসিক স্বাস্থ্যসেবা গ্রহণ করছেন।',
                'স্থানীয় নারীরা এই উদ্যোগকে স্বাগত জানিয়েছেন, কারণ এতে সময় ও অর্থ দুটোই সাশ্রয় হচ্ছে।',
            ]],
            'eco-tourism-trails-gain-popularity-in-bandarban-hills' => ['বান্দরবানের পাহাড়ে জনপ্রিয় হচ্ছে পরিবেশবান্ধব কমিউনিটি পর্যটন ট্রেইল', 'স্থানীয় ক্ষুদ্র নৃগোষ্ঠীর হোমস্টে এবং প্লাস্টিকমুক্ত পরিবেশ রক্ষায় পর্যটকদের সচেতনতা বৃদ্ধি।', [
                'বান্দরবানের নয়নাভিরাম পাহাড়ি ট্রেইলগুলোতে স্থানীয় নৃগোষ্ঠী পরিচালিত পরিবেশবান্ধব ট্র্যাকিং দারুণ জনপ্রিয় হয়ে উঠেছে।',
                'পর্যটকরা পাহাড়ি গ্রামে হোমস্টেতে থেকে ঐতিহ্যবাহী খাবার ও সংস্কৃতির সান্নিধ্য উপভোগ করছেন।',
                'পর্যটন থেকে অর্জিত আয়ের একটি অংশ ব্যয় হচ্ছে স্থানীয় স্কুল এবং বনায়ন প্রকল্পে।',
            ]],
            'traditional-handloom-weavers-bridge-heritage-and-modern-fashion' => ['ঐতিহ্যবাহী জামদানি ও খাদি শিল্পে আধুনিক ফ্যাশনের মেলবন্ধন', 'হাতে বোনা তাঁতবস্ত্রকে সমকালীন পোশাকে রূপ দিচ্ছেন তরুণ ফ্যাশন ডিজাইনাররা।', [
                'সোনারগাঁ ও টাঙ্গাইলের প্রবীণ তাঁতিরা তরুণ ডিজাইনারদের সাথে মিলে জামদানি ও খাদির নকশায় আধুনিকতার ছোঁয়া এনেছেন।',
                'প্রাকৃতিক রং ও শতভাগ সুতির এই পোশাকগুলো দেশের গণ্ডি পেরিয়ে আন্তর্জাতিক বাজারেও সুনাম কুড়াচ্ছে।',
                'এই অংশীদারত্বের ফলে তাঁতশিল্পের সাথে জড়িত পরিবারগুলোর আয়ের স্থায়ী পথ সুগম হচ্ছে।',
            ]],

            'community-radio-expands-agriculture-bulletins' => ['কমিউনিটি রেডিওর দৈনিক কৃষি বুলেটিন সম্প্রসারণ', 'নতুন আঞ্চলিক পর্বে বাজারদর, আবহাওয়া ও কৃষি সম্প্রসারণ কর্মকর্তাদের পরামর্শ প্রচার করা হবে।', [
                'কৃষকদের আরও নিয়মিত আবহাওয়া, ফসল ও বাজারের তথ্য দিতে আঞ্চলিক রেডিও বুলেটিন বাড়ানো হচ্ছে।',
                'স্থানীয় শ্রোতাদের সঙ্গে আলোচনা করে সম্প্রচারের সময় নির্ধারণ করা হবে এবং দিনের কাজে বাইরে থাকা মানুষের জন্য তা পুনঃপ্রচার করা হবে।',
                'শ্রোতারা ভবিষ্যৎ পর্বের জন্য প্রশ্নও পাঠাতে পারবেন বলে প্রযোজকেরা জানিয়েছেন।',
            ]],
            'explainer-how-the-bangabandhu-tunnel-changes-southern-logistics' => ['ভিডিও এক্সপ্লেইনার: বঙ্গবন্ধু টানেল যেভাবে বদলে দিচ্ছে দক্ষিণবঙ্গের অর্থনীতি', 'কর্ণফুলীর তলদেশের টানেল দিয়ে কীভাবে শিল্পাঞ্চল ও চট্টগ্রাম বন্দরের যানজটমুক্ত যোগাযোগ গড়ে উঠেছে।', [
                'কর্ণফুলী নদীর তলদেশে নির্মিত বঙ্গবন্ধু টানেলের কৌশলগত গুরুত্ব ও অর্থনৈতিক সম্ভাবনা নিয়ে একটি বিশেষ ভিডিও বিশ্লেষণ প্রকাশ করা হয়েছে।',
                'অ্যানিমেশনের মাধ্যমে দেখানো হয়েছে কীভাবে শিল্পাঞ্চলের মালামাল যানজট এড়িয়ে দ্রুত বন্দরে পৌঁছাতে পারছে।',
                'অর্থনীতিবিদ ও প্রকৌশলীরা বলছেন, এই অবকাঠামো আগামী দিনে দেশি-বিদেশি বড় বিনিয়োগের কেন্দ্রবিন্দু হবে।',
            ]],
            'voices-from-the-delta-living-with-the-tides' => ['ভিডিও ডকুমেন্টারি: জোয়ার-ভাটার দেশে মানুষের টিকে থাকার গল্প', 'মেঘনার মোহনায় ভাসমান জীবন ও নদীমাতৃক বাংলার সাধারণ মানুষের সাহসিকতার প্রামাণ্যচিত্র।', [
                'দক্ষিণাঞ্চলের জলপথ ও চরাঞ্চলের মানুষের প্রাত্যহিক জীবনসংগ্রাম নিয়ে তৈরি হয়েছে বিশেষ প্রামাণ্যচিত্র।',
                'নৌকার মাঝি ও চরের শিক্ষকরা তুলে ধরেছেন নদীর সাথে তাদের আজীবনের গভীর সম্পর্কের কথা।',
                'প্রামাণ্যচিত্রটি সামাজিক মাধ্যমে ব্যাপক প্রশংসিত হয়েছে এবং জলবায়ু সচেতনতায় অবদান রাখছে।',
            ]],
            'field-report-inside-the-national-seed-preservation-vault' => ['ভিডিও রিপোর্ট: গাজীপুরের জাতীয় বীজ সংরক্ষণাগারের ভেতরের দৃশ্য', 'ভবিষ্যৎ প্রজন্মের খাদ্য সুরক্ষায় হাজারো দেশীয় শস্যবীজ ক্রায়োজেনিক প্রযুক্তিতে সংরক্ষণের বিশেষ প্রতিবেদন।', [
                'বাংলাদেশ কৃষি গবেষণা ইনস্টিটিউটের অত্যাধুনিক বীজ সংরক্ষণাগারের ওপর নির্মিত একটি বিশেষ ভিডিও প্রতিবেদন।',
                'বিজ্ঞানী ও গবেষকরা দেখিয়েছেন কীভাবে তরল নাইট্রোজেনের মাধ্যমে হিমাঙ্কের নিচে শস্যবীজের জিনগত বৈশিষ্ট্য অক্ষুণ্ণ রাখা হয়।',
                'এই গবেষণা কেন্দ্রটি যেকোনো প্রাকৃতিক বিপর্যয়ে দেশের খাদ্য নিরাপত্তার এক অনন্য নির্ভরতার প্রতীক।',
            ]],

            'remittance-inflows-reach-six-month-high-ahead-of-festivals' => ['উৎসবকে সামনে রেখে প্রবাসীদের পাঠানো রেমিট্যান্সে নতুন উল্লম্ফন', 'বৈধ পথে তাৎক্ষণিক সুবিধা পাওয়ায় বৃদ্ধি পেয়েছে প্রবাসী আয়ের গতি, স্বস্তি বৈদেশিক মুদ্রার মজুতে।', [
                'গত ছয় মাসের মধ্যে সর্বোচ্চ মাসিক রেমিট্যান্স আয় দেশে এসেছে বলে জানিয়েছে বাংলাদেশ ব্যাংক।',
                'প্রবাসী কর্মীদের জন্য সহজ ব্যাংকিং অ্যাপ এবং সরকারি নগদ প্রণোদনা ব্যাংকিং চ্যানেলে রেমিট্যান্স বৃদ্ধিতে বড় ভূমিকা রেখেছে।',
                'অর্থনীতিবিদরা বলছেন, রেমিট্যান্সের এই ঊর্ধ্বমুখী ধারা দেশের আমদানি ব্যয় পরিশোধ ও সামগ্রিক অর্থনীতিকে স্থিতিশীল রাখবে।',
            ]],
            'inflation-moderates-as-supply-chain-logistics-stabilize' => ['সরবরাহ ব্যবস্থা স্বাভাবিক হওয়ায় খাদ্য মূল্যস্ফীতিতে স্বস্তির লক্ষণ', 'কৃষক থেকে ভোক্তা পর্যায়ে সরাসরি পণ্য পরিবহন ও বাম্পার ফলনে নিত্যপণ্যের দাম নিয়ন্ত্রণে।', [
                'পরিসংখ্যান ব্যুরোর সাম্প্রতিক প্রতিবেদনে দেখা গেছে, বিগত কয়েক মাসের তুলনায় নিত্যপণ্যের মূল্যস্ফীতি নিম্নমুখী হয়েছে।',
                'মাঠপর্যায়ে বাজার মনিটরিং এবং কৃষকদের সরাসরি বাজারজাতকরণ সুবিধা মধ্যস্বত্বভোগীদের দৌরাত্ম্য কমিয়েছে।',
                'বিশেষজ্ঞরা পরামর্শ দিয়েছেন, প্রতিটি জেলায় আধুনিক হিমাগার স্থাপন করলে সারা বছর নিত্যপণ্যের দাম স্থিতিশীল রাখা সম্ভব হবে।',
            ]],
            'renewable-energy-investments-surge-across-industrial-zones' => ['রপ্তানিমুখী শিল্পাঞ্চলে বাড়ছে সৌরবিদ্যুতের ব্যবহার', 'কারখানার ছাদে রুফটপ সোলার প্যানেল স্থাপন করে সাশ্রয় হচ্ছে বিদ্যুৎ খরচ, কমছে কার্বন ফুটপ্রিন্ট।', [
                'দেশের শীর্ষস্থানীয় তৈরি পোশাক ও শিল্প কারখানাগুলোর ছাদে বসানো হয়েছে শত শত মেগাওয়াট ক্ষমতার সৌর প্যানেল।',
                'ব্যাংকগুলোর সহজ শর্তের পরিবেশবান্ধব পুনঃঅর্থায়ন তহবিল এই সবুজ বিপ্লবে গতি এনেছে।',
                'শিল্পমালিকরা বলছেন, সৌরবিদ্যুতের ব্যবহার আন্তর্জাতিক বাজারে আমাদের পণ্যের গ্রহণযোগ্যতা বহুগুণ বাড়িয়ে দিচ্ছে।',
            ]],

            'mangrove-afforestation-protects-hundreds-of-coastal-villages' => ['উপকূলীয় সবুজ বেষ্টনী রক্ষা করছে উপকূলের সহস্রাধিক গ্রাম', 'জনগণের অংশগ্রহণে ম্যানগ্রোভ বনায়ন সামুদ্রিক জলোচ্ছ্বাসের তীব্রতা কমাতে সক্ষম হয়েছে।', [
                'উপকূলীয় জেলাগুলোতে বন বিভাগ ও স্থানীয় জনগণের যৌথ উদ্যোগে হাজার হাজার হেক্টর চরে ম্যানগ্রোভ বাগান সৃজন করা হয়েছে।',
                'গবেষণায় দেখা গেছে, উপকূলীয় এই বনাঞ্চল ঘূর্ণিঝড়ের সময় বাতাসের গতি ও জলোচ্ছ্বাসের ঢেউ উল্লেখযোগ্যভাবে প্রতিহত করে।',
                'বন সুরক্ষা কমিটির সদস্যরা বনের ক্ষতি না করে কাঁকড়া চাষ ও মধু সংগ্রহের মাধ্যমে স্বাবলম্বী হচ্ছেন।',
            ]],
            'early-warning-cyclone-networks-cut-response-times-to-minutes' => ['উপকূলে স্মার্ট দুর্যোগ সতর্কবার্তা ব্যবস্থায় প্রাণহানি শূন্যের কোঠায়', 'স্বয়ংক্রিয় সাইরেন, মোবাইল এসএমএস ও বেতার সংকেতে চরাঞ্চলের মানুষকে দ্রুত আশ্রয়কেন্দ্রে নেওয়ার প্রস্তুতি।', [
                'উপকূলীয় দুর্যোগপ্রবণ এলাকায় আধুনিক সতর্কবার্তা টাওয়ার ও ডিজিটাল মেসেজিং নেটওয়ার্ক স্থাপন সম্পন্ন হয়েছে।',
                'স্যাটেলাইটের পূর্বাভাস পাওয়ার সাথে সাথে স্বয়ংক্রিয় সাইরেন ও স্থানীয় ভাষায় মাইকিংয়ের মাধ্যমে সংকেত প্রচার করা হয়।',
                'সাম্প্রতিক সচেতনতামূলক মহড়ায় দেখা গেছে, সংকেত পাওয়ার অল্প সময়ের মধ্যেই ঝুঁকিপূর্ণ মানুষ নিরাপদ আশ্রয়ে পৌঁছাতে সক্ষম হচ্ছেন।',
            ]],
            'saline-tolerant-crop-varieties-expand-in-southern-polders' => ['দক্ষিণাঞ্চলের লবণাক্ত জমিতে বিজ্ঞানীদের উদ্ভাবিত লবণসহিষ্ণু ফসলের বাম্পার ফলন', 'বর্ষা পরবর্তী শুষ্ক মৌসুমেও অনাবাদি থাকছে না উপকূলের জমি, চাষ হচ্ছে বিশেষ সরিষা ও ডাল।', [
                'উপকূলীয় পোল্ডার এলাকার কৃষকেরা দেশের বিজ্ঞানীদের উদ্ভাবিত নতুন জাতের লবণসহিষ্ণু ফসল চাষ করে সাফল্য পেয়েছেন।',
                'যেসব জমি আগে বছরের বেশিরভাগ সময় অনাবাদি থাকত, সেখানে এখন নিয়মিত তেলবীজ ও ডাল জাতীয় ফসল উৎপাদিত হচ্ছে।',
                'কৃষি সম্প্রসারণ অধিদপ্তর উপকূলের অন্যান্য অঞ্চলেও এই বিশেষ জাতের বীজ বিনা মূল্যে ছড়িয়ে দিচ্ছে।',
            ]],

            'pahela-baishakh-preparations-begin-with-traditional-mangol-shobhajatra' => ['পহেলা বৈশাখ বরণে চারুকলায় বর্ণিল মঙ্গল শোভাযাত্রার জোর প্রস্তুতি', 'শান্তি ও সম্প্রীতির বার্তা ছড়িয়ে দিতে চারুকলার শিক্ষার্থীরা তৈরি করছেন লোকজ মোটিফের মুখোশ ও শিল্পকর্ম।', [
                'বাংলা নববর্ষকে স্বাগত জানাতে দেশের সব সাংস্কৃতিক অঙ্গনে শুরু হয়েছে উৎসবমুখর প্রস্তুতি।',
                'ঢাকা বিশ্ববিদ্যালয়ের চারুকলা অনুষদে রাত-দিন পরিশ্রম করে তৈরি করা হচ্ছে ঐতিহ্যবাহী লোকজ পুতুল ও শান্তির প্রতীকী ভাস্কর্য।',
                'ইউনেস্কোর স্বীকৃতিপ্রাপ্ত মঙ্গল শোভাযাত্রায় অংশ নিতে বরাবরের মতো এবারও দেশি-বিদেশি মানুষের বিপুল সমাগম প্রত্যাশা করা হচ্ছে।',
            ]],
            'baul-music-archive-unveils-rare-recordings-from-kushtia' => ['কুষ্টিয়ার লালন আখড়া ও বাউল গানের দুর্লভ অডিও ডিজিটাল আর্কাইভে উন্মুক্ত', 'লোকদর্শন ও লালন সাঁইজির অমর বাণী বিশ্বের গবেষকদের জন্য অনলাইনে সংরক্ষণ।', [
                'বাউল গান ও বাংলার লোকদর্শনের ওপর গবেষণার সুবিধার্থে একটি সমৃদ্ধ ডিজিটাল আর্কাইভ সাধারণের জন্য উন্মুক্ত করা হয়েছে।',
                'একতারা ও দোতারার অনন্য ঝংকারে গ্রামীণ প্রবীণ বাউলদের গাওয়া শত শত দুষ্প্রাপ্য গানের মূল রেকর্ডিং এতে স্থান পেয়েছে।',
                'আন্তর্জাতিক সংগীত গবেষকরা বলছেন, এই দার্শনিক গানগুলো বাংলার মানবতাবাদী চিন্তাধারাকে বিশ্বদরবারে উজ্জ্বল করেছে।',
            ]],
            'national-book-fair-records-record-footfall-and-youth-authors' => ['অমর একুশে বইমেলায় উপচে পড়া ভিড়, তরুণ লেখকদের বই নিয়ে আগ্রহ তুঙ্গে', 'পাঠক-লেখকের প্রাণবন্ত আড্ডায় মুখরিত বাংলা একাডেমি ও সোহরাওয়ার্দী উদ্যান প্রাঙ্গণ।', [
                'অমর একুশে বইমেলার প্রতিটি বিকেলে বইপ্রেমী মানুষের পদচারণায় মুখরিত হয়ে উঠছে মেলা প্রাঙ্গণ।',
                'প্রকাশকরা জানিয়েছেন, বিজ্ঞানবিষয়ক বই, গল্প-উপন্যাস ও গবেষণাধর্মী রচনার বিক্রি এবার সবচেয়ে বেশি।',
                'মেলামঞ্চে নিয়মিত অনুষ্ঠিত হচ্ছে সাহিত্য আলোচনা ও আবৃত্তি সন্ধ্যা, যা তরুণ প্রজন্মকে বই পড়ার প্রতি আকৃষ্ট করছে।',
            ]],

            'biochemists-develop-rapid-water-purity-testing-kits' => ['সহজে পানির বিশুদ্ধতা পরীক্ষার সাশ্রয়ী টেস্ট স্ট্রিপ উদ্ভাবন করলেন দেশীয় গবেষকরা', 'কয়েক মিনিটের মধ্যেই আর্সেনিক, ক্ষতিকর ধাতু ও ব্যাকটেরিয়ার উপস্থিতি শনাক্ত করা সম্ভব।', [
                'বিশ্ববিদ্যালয়ের একদল গবেষক সাধারণ মানুষের ব্যবহার উপযোগী কম খরচের পানির বিশুদ্ধতা পরীক্ষার স্ট্রিপ তৈরি করেছেন।',
                'পানিতে ক্ষতিকর আর্সেনিক বা জীবাণু থাকলে স্ট্রিপের রঙের পরিবর্তনের মাধ্যমে ফলাফল জানা যায়, কোনো ল্যাবরেটরি ছাড়াই।',
                'জনস্বাস্থ্য প্রকৌশল অধিদপ্তর এই স্ট্রিপগুলো প্রত্যন্ত অঞ্চলের স্বাস্থ্যকর্মীদের মাঝে বিতরণের পরিকল্পনা করেছে।',
            ]],
            'space-research-station-tracks-weather-patterns-with-high-accuracy' => ['স্পারসোতে আধুনিক স্যাটেলাইট প্রযুক্তির মাধ্যমে আবহাওয়ার নিখুঁত পূর্বাভাস', 'বৃষ্টিপাত ও আকস্মিক পাহাড়ি ঢলের আগাম তথ্যে রক্ষা পাচ্ছে কৃষকের মাঠের ফসল।', [
                'মহাকাশ গবেষণা ও দূর অনুধাবন প্রতিষ্ঠান (স্পারসো) তাদের স্যাটেলাইট তথ্য বিশ্লেষণ কেন্দ্রকে অত্যাধুনিক করেছে।',
                'মেঘমালার গতিবিধি ও নদী অববাহিকার জলস্তর বিশ্লেষণের মাধ্যমে পাঁচ দিন আগেই সম্ভাব্য বন্যার পূর্বাভাস দেওয়া সম্ভব হচ্ছে।',
                'কৃষিবিদ ও দুর্যোগ ব্যবস্থাপকেরা জানিয়েছেন, এই আগাম তথ্য হাওর ও পাহাড়ি এলাকার ফসল রক্ষায় গুরুত্বপূর্ণ সহায়তা দিচ্ছে।',
            ]],
            'ai-powered-crop-disease-detection-app-rolled-out-for-farmers' => ['মোবাইলে ফসলের ছবি তুলে রোগ শনাক্তে কৃত্রিম বুদ্ধিমত্তাভিত্তিক অ্যাপ', 'ফসলের পাতার ছবি আপলোড করলেই মিলছে বাংলায় সমাধান ও প্রয়োজনীয় ওষুধের পরামর্শ।', [
                'কৃত্রিম বুদ্ধিমত্তা চালিত একটি নতুন মোবাইল অ্যাপ দেশের কৃষকদের রোগবালাই ব্যবস্থাপনায় দারুণ সহায়তা করছে।',
                'ধান, পাট বা সবজির আক্রান্ত পাতার ছবি তুললেই অ্যাপটি স্বয়ংক্রিয়ভাবে রোগের কারণ ও প্রতিকার প্রদর্শন করে।',
                'মাঠপর্যায়ে দেখা গেছে, এই প্রযুক্তির কারণে অতিরিক্ত ও অপ্রয়োজনীয় কীটনাশকের ব্যবহার উল্লেখযোগ্যভাবে হ্রাস পেয়েছে।',
            ]],

            'freshwater-dolphin-sanctuaries-see-encouraging-population-rise' => ['পদ্মা-যমুনার শুশুক অভয়াশ্রমে আশাব্যঞ্জকভাবে বেড়েছে নদীর শুশুকের সংখ্যা', 'ক্ষতিকর জালের ব্যবহার রোধ এবং নদী তীরের মানুষের সচেতনতায় রক্ষা পাচ্ছে এই বিপন্ন জলজ প্রাণী।', [
                'নদীগুলোতে ডলফিন বা শুশুকের অবাধ বিচরণের জন্য ঘোষিত সংরক্ষিত এলাকায় আশাব্যঞ্জক সংখ্যাবৃদ্ধি লক্ষ্য করা গেছে।',
                'স্থানীয় জেলে সম্প্রদায়ের সহায়তায় অবৈধ কারেন্ট জালের ব্যবহার বন্ধ করায় শুশুকের মৃত্যুহার শূন্যের কোঠায় নেমে এসেছে।',
                'পরিবেশবিদরা বলছেন, নদীতে শুশুকের উপস্থিতি সামগ্রিক জলজ বাস্তুতন্ত্রের সুস্বাস্থ্যের নির্ভরযোগ্য প্রমাণ।',
            ]],
            'plastic-waste-reduction-initiative-cleans-major-urban-canals' => ['নগরীর খালগুলো থেকে শত শত টন প্লাস্টিক বর্জ্য অপসারণ ও পুনর্ব্যবহার', 'নাগরিক উদ্যোগ ও পরিচ্ছন্নতা অভিযানে স্বাভাবিক হয়েছে শহরের পানি নিষ্কাশন ব্যবস্থা।', [
                'নগরীর প্রধান খালগুলো থেকে ভাসমান প্লাস্টিক বর্জ্য অপসারণের জন্য ব্যাপক পরিচ্ছন্নতা অভিযান পরিচালিত হয়েছে।',
                'সংগৃহীত প্লাস্টিক বর্জ্য বিশেষ কারখানায় প্রক্রিয়াজাত করে ফুটপাতের পেভার টাইলস ও নির্মাণ সামগ্রীতে রূপান্তর করা হচ্ছে।',
                'সিটি কর্পোরেশন খালের বিভিন্ন মুখে বিশেষ ভাসমান ট্র্যাশ ট্র্যাপ স্থাপন করেছে যাতে নতুন করে বর্জ্য জমতে না পারে।',
            ]],
            'community-forest-guard-groups-awarded-for-conservation-efforts' => ['লাউয়াছড়া ও সাতছড়ির চিরহরিৎ বন সুরক্ষায় প্রশংসিত স্থানীয় বন পাহারা দল', 'গাছ কাটা রোধ ও বন্যপ্রাণীর নিরাপদ আবাসস্থল সংরক্ষণে অনন্য অবদানের স্বীকৃতি।', [
                'চিরহরিৎ বনাঞ্চলের জীববৈচিত্র্য রক্ষায় অনন্য ভূমিকা রাখায় স্থানীয় বন রক্ষা কমিটিকে জাতীয় সম্মাননা প্রদান করা হয়েছে।',
                'গ্রামবাসী পালাক্রমে পাহারা দিয়ে মূল্যবান বনজ সম্পদ ও বিলুপ্তপ্রায় প্রাণীদের অভয়াশ্রম সুরক্ষিত রাখছেন।',
                'ইকোট্যুরিজম ও নার্সারি থেকে অর্জিত আয় ব্যয় করা হচ্ছে গ্রামের শিক্ষা ও সামাজিক উন্নয়নমূলক কর্মকাণ্ডে।',
            ]],

            'bangladesh-betar-celebrates-milestone-with-next-gen-audio-portal' => ['আধুনিক ডিজিটাল অডিও পোর্টাল ও নিউজ প্ল্যাটফর্ম চালু করল বাংলাদেশ বেতার', 'বিশ্বজুড়ে কোটি কোটি শ্রোতার জন্য লাইভ রেডিও, পডকাস্ট, আর্কাইভ সংগীত ও বস্তুনিষ্ঠ সংবাদসেবা।', [
                'বাংলাদেশ বেতার তাদের অত্যাধুনিক ডিজিটাল সেবা চালু করেছে, যেখানে যুক্ত হয়েছে লাইভ রেডিও স্ট্রিমিং ও সমৃদ্ধ আর্কাইভ।',
                'স্মার্টফোন ও ওয়েব ব্রাউজার থেকে এখন সরাসরি শোনা যাচ্ছে দেশের প্রতিটি আঞ্চলিক কেন্দ্রের সম্প্রচার ও তথ্যবহুল অনুষ্ঠানমালা।',
                'প্রবাসী ও তরুণ প্রজন্মের শ্রোতারা এই প্ল্যাটফর্মের সমৃদ্ধ সাংস্কৃতিক সম্ভার ও দ্রুতগতির সংবাদের ভূয়সী প্রশংসা করছেন।',
            ]],
            'fact-checking-network-partners-with-rural-community-radio' => ['গুজব ও ভুয়া তথ্য প্রতিরোধে কমিউনিটি রেডিও ও ফ্যাক্টচেকারদের যৌথ উদ্যোগ', 'সামাজিক যোগাযোগমাধ্যমের বিভ্রান্তিকর তথ্যের সত্যতা যাচাই করে নিয়মিত বুলেটিন প্রচার।', [
                'অনলাইনের অপতথ্য ও গুজব সম্পর্কে তৃণমূলের মানুষকে সচেতন করতে কমিউনিটি রেডিওগুলোতে বিশেষ ফ্যাক্ট-চেক পর্ব চালু হয়েছে।',
                'স্বাস্থ্য, সরকারি অনুদান ও সামাজিক বিষয়ে ছড়িয়ে পড়া ভুল তথ্যের সঠিক রূপ তুলে ধরছেন সাংবাদিকেরা।',
                'স্থানীয় সুশীল সমাজ জানিয়েছে, এই সচেতনতামূলক প্রচারণার ফলে গ্রামীণ মানুষ অনলাইনের প্রতারণা থেকে রক্ষা পাচ্ছেন।',
            ]],
            'journalism-fellowships-awarded-for-investigative-climate-reporting' => ['পরিবেশ ও অনুসন্ধানমূলক সাংবাদিকতায় ১২ তরুণ সাংবাদিককে ফেলোশিপ প্রদান', 'উপকূলের লবণাক্ততা, ভূগর্ভস্থ পানি ও নবায়নযোগ্য জ্বালানির ওপর মাঠপর্যায়ে অনুসন্ধানী প্রতিবেদন তৈরির সুযোগ।', [
                'জাতীয় গণমাধ্যম প্রতিষ্ঠান দেশের প্রতিভাবান সাংবাদিকদের জন্য বিশেষ অনুসন্ধানমূলক ফেলোশিপ ঘোষণা করেছে।',
                'ফেলোশিপপ্রাপ্ত সাংবাদিকেরা ডেল্টা অঞ্চলের নদীভাঙন, উপকূলের জীবিকা ও শিল্পাঞ্চলের বর্জ্য ব্যবস্থাপনা নিয়ে গভীর অনুসন্ধান করবেন।',
                'জ্যেষ্ঠ সাংবাদিকেরা তাদের তথ্যভিত্তিক ও মাল্টিমিডিয়া সাংবাদিকতার ওপর প্রয়োজনীয় মেন্টরশিপ ও দিকনির্দেশনা প্রদান করবেন।',
            ]],
        ];

        foreach ($news as $position => [$slug, $title, $summary, $category, $image, $readTime, $minutesAgo, $viewsCount, $isFeatured, $body]) {
            [$titleBn, $summaryBn, $bodyBn] = $newsBn[$slug];
            $categoryId = NewsCategory::query()->where('name', $category)->value('id');
            NewsArticle::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'news_category_id' => $categoryId,
                'title' => $title,
                'title_bn' => $titleBn,
                'summary' => $summary,
                'summary_bn' => $summaryBn,
                'category' => $category,
                'body' => $body,
                'body_bn' => $bodyBn,
                'image_path' => 'portal/demo/'.$image,
                'read_time_minutes' => $readTime,
                'views_count' => $viewsCount,
                'position' => $position,
                'is_featured' => $isFeatured,
                'is_published' => true,
                'published_at' => now()->subMinutes($minutesAgo),
            ]);
        }

        $shows = [
            // --- Featured / Drama / Series ---
            ['the-last-transmission', 'The Last Transmission', 'New original drama', 'In a radio studio during the final weeks of 1971, a young broadcaster discovers that one carefully chosen message can travel farther than fear.', 'Drama', 'watch-hero.png', 2026, 'PG', true, [
                ['The Signal', 46, 'Maya arrives for a night shift that will change the course of the station.'],
                ['Between Frequencies', 44, 'A hidden message forces the team to decide who they can trust.'],
                ['The Last Transmission', 52, 'The studio prepares one final broadcast as dawn approaches.'],
            ]],
            ['chhaya-shikari', 'Chhaya Shikari: The Shadow Hunter', 'Crime thriller series', 'A dedicated cyber-detective and a seasoned port inspector unravel a high-stakes smuggling syndicate operating along the coast.', 'Crime Drama', 'watch-hero.png', 2026, '16+', true, [
                ['Midnight Cargo', 42, 'A suspicious container at the outer anchorage triggers a clandestine investigation.'],
                ['Encrypted Waters', 45, 'Digital clues point to an offshore server farm masking vessel coordinates.'],
                ['The Final Trap', 48, 'Law enforcement stages a coordinated multi-agency raid before dawn.'],
            ]],
            ['shukhi-shongshar', 'Shukhi Shongshar', 'Popular family drama', 'Generations navigate love, career ambitions and cherished rural traditions in a vibrant multi-generational riverside home.', 'Popular Programmes', 'watch-kids.png', 2026, 'G', true, [
                ['The Family Feast', 32, 'Relatives gather for the annual harvest celebration with surprise announcements.'],
                ['New Beginnings', 30, 'The youngest daughter receives a scholarship to study renewable energy.'],
                ['Bridges of Understanding', 34, 'Elders and youth find common ground on modernizing the family craft studio.'],
            ]],
            ['bhoot-shonibar', 'Bhoot Shonibar: Midnight Radio Tales', 'Spine-chilling horror anthology', 'A late-night radio host reads verified listener encounters with the supernatural from remote corners of Bengal.', 'Horror', 'watch-hero.png', 2026, '18+', true, [
                ['The Abandoned Zamindar Bari', 38, 'A group of college researchers spends a stormy night inside a haunted mansion.'],
                ['Whispers in the Fog', 35, 'A lone boatman hears haunting melodies drifting from a submerged river island.'],
                ['The Red Trunk', 40, 'An antique heirloom chest brings eerie premonitions to its new owners.'],
            ]],

            // --- Documentaries ---
            ['rivers-that-remember', 'Rivers That Remember', 'Documentary series', 'Travel with the boat communities whose stories, livelihoods and songs follow the changing waterways of Bangladesh.', 'Documentary', 'watch-river.png', 2026, 'G', true, [
                ['Morning Tide', 28, 'A fishing family reads the river before sunrise.'],
                ['Moving Banks', 31, 'Communities adapt as familiar channels shift.'],
                ['Songs Downstream', 29, 'Music carries memory from one generation to the next.'],
            ]],
            ['voices-of-betar', 'Voices of Betar', 'Archive documentary', 'Presenters, engineers and performers revisit the historic moments that made public radio part of everyday national life.', 'Documentary', 'watch-hero.png', 2025, 'G', false, [
                ['Behind the Microphone', 48, 'The legendary people who gave a national broadcaster its timeless voice.'],
                ['Frequencies of Freedom', 50, 'The clandestine broadcast relays of the 1971 Swadhin Bangla Betar Kendra.'],
            ]],
            ['tomorrows-builders', "Tomorrow's Builders", 'Factual science series', 'Young student inventors turn classroom ideas into practical agricultural and robotic tools for their communities.', 'Documentary', 'news-tech.png', 2026, 'G', false, [
                ['Small Machines, Big Ideas', 26, 'A rural robotics club prepares for its first national showcase.'],
                ['Solar on the Water', 28, 'Engineering undergraduates build floating solar pumps for irrigation.'],
            ]],
            ['ready-together', 'Ready Together', 'Community resilience stories', 'Meet the extraordinary coastal volunteers strengthening local disaster preparedness before severe monsoons arrive.', 'Documentary', 'news-coast.png', 2026, 'G', false, [
                ['The Shelter Team', 27, 'Neighbours turn cyclone preparedness into an empowering shared routine.'],
                ['After the Storm', 29, 'Community brigades restore drinking water tube-wells in record time.'],
            ]],
            ['archeology-of-mahasthangarh', 'Echoes of Mahasthangarh', 'Historical archaeology documentary', 'Excavations unveil two millennia of ancient urban civilisations along the banks of the Karatoya River.', 'Documentary', 'watch-river.png', 2026, 'G', false, [
                ['Layers of Time', 34, 'Archaeologists unearth terracotta seals and ancient citadel fortifications.'],
            ]],

            // --- Living and Culture ---
            ['monsoon-kitchen', 'The Monsoon Kitchen', 'Food and cultural journeys', 'Celebrated regional home cooks share seasonal monsoon recipes, culinary secrets, and family histories.', 'Living and Culture', 'news-rice.png', 2026, 'G', true, [
                ['First Rain Delicacies', 24, 'A traditional feast built around the arrival of fresh monsoon rains.'],
                ['Hilsa and Mustard Dreams', 26, 'Authentic riverbank cooking techniques passed down through generations.'],
            ]],
            ['songs-of-the-courtyard', 'Songs of the Courtyard', 'Live musical performances', 'An intimate musical evening of classical ragas and soulful folk traditions recorded live with master instrumentalists.', 'Culture', 'watch-music.png', 2026, 'G', false, [
                ['Folk Roads', 42, 'Timeless songs shaped by travel, rivers and village life.'],
                ['Poetry in Raga', 39, 'Classical vocalists and sitar maestros meet in a spellbinding arrangement.'],
            ]],
            ['festivals-of-the-delta', 'Festivals of the Delta', 'Heritage cultural series', 'Experience the pulsating colours, sacred chants, boat races and carnivals that celebrate seasonal transitions.', 'Living and Culture', 'watch-music.png', 2026, 'G', false, [
                ['The Great Boat Race', 30, 'Rowers sing rhythmic sari gaan as long racing boats slice through river waves.'],
                ['Harvest Lanterns', 28, 'Villagers light thousand terracotta lamps during rural autumn celebrations.'],
            ]],

            // --- Comedy ---
            ['bhalobashar-koutuk', 'Bhalobashar Koutuk', 'Classic comedy theatre', 'A witty village matchmaker gets entangled in his own comical misunderstandings during wedding preparations.', 'Comedy', 'watch-kids.png', 2026, 'G', false, [
                ['The Letter Mix-up', 25, 'Two identical letters sent to different households spark hilarious confusion.'],
                ['The Fake Astrologer', 28, 'A clever scheme to reveal true love leads to riotous village laughter.'],
            ]],
            ['gramer-hasir-golpo', 'Gramer Hasir Golpo', 'Rural satire and humour', 'Witty everyday encounters between clever tea-stall philosophers, eccentric headmasters, and village youth.', 'Comedy', 'news-rice.png', 2026, 'G', false, [
                ['The Great Tea Debate', 22, 'A debate over football tactics consumes the entire village market.'],
                ['The Modern Bicycle', 24, 'The postman buys a smart electric bicycle with unexpected talking features.'],
            ]],

            // --- Horror ---
            ['raater-chhaya', 'Raater Chhaya: Shadows of Midnight', 'Supernatural thriller', 'An investigative paranormal journalist probes mysterious nocturnal sightings in the misty tea gardens of Sylhet.', 'Horror', 'watch-hero.png', 2026, '16+', false, [
                ['The Ghost of Estate 7', 36, 'Strange occurrences baffle night guards in the oldest British-era tea estate.'],
                ['The Vanishing Trail', 38, 'Footprints that lead into deep forest ravines and suddenly disappear.'],
            ]],
            ['nodi-kuler-pretopuri', 'Nodi Kuler Pretopuri', 'Haunted river folklore', 'A documentary crew investigating sunken shipwrecks discovers forgotten legends that refuse to stay submerged.', 'Horror', 'watch-river.png', 2026, '16+', false, [
                ['Under the Dark Water', 32, 'Sonar scans reveal an ancient vessel not listed in any maritime archive.'],
            ]],

            // --- News and Current Affairs ---
            ['betar-shongbad-bortika', 'Betar Shongbad Bortika', 'Weekly current affairs analysis', 'Senior journalists and economic policy experts dissect the biggest national and global headlines of the week.', 'News and Current Affairs', 'news-hero.png', 2026, 'G', false, [
                ['The Economic Horizon', 40, 'Deep dive into national budget allocations and export diversification.'],
                ['Diplomacy in Focus', 38, 'Analyzing South Asian trade pacts and climate financing accords.'],
            ]],
            ['mukhomukhi-bortoman', 'Mukhomukhi Bortoman', 'Hard-hitting interview series', 'Cabinet ministers, civic leaders, and innovators answer direct citizen questions on public accountability.', 'News and Current Affairs', 'news-tech.png', 2026, 'G', false, [
                ['Digital Governance Dialogue', 42, 'The ICT Minister discusses citizen service automation and data privacy.'],
            ]],

            // --- Crime Drama ---
            ['raater-shongket', 'Raater Shongket: Code of the Night', 'Procedural police drama', 'An elite CID homicide unit uses cutting-edge forensic science to solve complex locked-room mysteries in Dhaka.', 'Crime Drama', 'watch-hero.png', 2026, '16+', false, [
                ['The Silent Witness', 44, 'A missing artist’s sketchbook holds the key to an elaborate art forgery heist.'],
                ['Digital Shadows', 46, 'Cyber-forensics teams trace encrypted transactions behind high-profile extortion.'],
            ]],
            ['shongshoy-o-shotti', 'Shongshoy O Shotti', 'Courtroom suspense drama', 'A fearless public defender takes on a seemingly unwinnable case defending an honest civil engineer.', 'Crime Drama', 'watch-hero.png', 2026, '16+', false, [
                ['The Blueprint', 45, 'Dam construction audit reports vanish right before crucial judicial proceedings.'],
            ]],

            // --- Movies & Short Films ---
            ['shurjer-shondhane', 'Shurjer Shondhane: In Search of Sun', 'Critically acclaimed period film', 'A sweeping cinematic story of three resilient families rebuilding their lives after the Great 1970 Bhola Cyclone.', 'Movies', 'watch-river.png', 2026, 'PG', false, [
                ['Full Feature Movie', 118, 'The complete remastered feature presentation in ultra-high-definition.'],
            ]],
            ['nodir-naam-madhumoti', 'Nodir Naam Madhumoti', 'Classic cinema masterpiece', 'An epic tale of love, patriotism and moral dilemmas during the Liberation War along the Madhumati River.', 'Movies', 'watch-hero.png', 2025, 'PG', false, [
                ['Full Feature Presentation', 124, 'Remastered historical motion picture with crystal-clear audio.'],
            ]],
            ['ekti-notun-bhor', 'Ekti Notun Bhor', 'Award-winning short film', 'A mute village boy invents an acoustic flute that can mimic migratory bird calls, uniting feuding neighbours.', 'Short Films', 'watch-kids.png', 2026, 'G', false, [
                ['Short Film', 22, 'Winner of Best Narrative Short at the Asian Youth Independent Film Festival.'],
            ]],
            ['stationer-chheleti', 'Stationer Chheleti: The Boy at the Station', 'Inspiring short film', 'A determined young newspaper vendor uses discarded textbooks to study for university admission exams.', 'Short Films', 'news-tech.png', 2026, 'G', false, [
                ['Complete Short Film', 19, 'A heartwarming portrayal of persistence, self-education and community warmth.'],
            ]],

            // --- Songs & Music ---
            ['desher-gaan-o-shur', 'Desher Gaan O Shur: Patriotic Melodies', 'Grand orchestral music specials', 'The Bangladesh Betar National Symphony Orchestra performs stirring renditions of timeless national anthems and folk tunes.', 'Songs', 'watch-music.png', 2026, 'G', false, [
                ['Songs of the Motherland', 45, 'Celebrated vocalists and live symphony orchestra in a breathtaking performance.'],
                ['Rhythms of Freedom', 40, 'Dynamic percussion and traditional folk instrumental ensemble.'],
            ]],
            ['baul-gaan-acoustic-sessions', 'Baul Gaan: Acoustic Riverbank Sessions', 'Unplugged musical recordings', 'Master folk minstrels perform mystical Lalon and Hason Raja lyrics beneath ancient banyan trees at sunset.', 'Songs', 'watch-music.png', 2026, 'G', false, [
                ['Mon Amar Moner Moto', 34, 'Soulful ektara and dotara melodies recorded live in Kushtia.'],
                ['Nodi Bhora Dheu', 36, 'Bhatiyali river songs celebrating the eternal cadence of the delta.'],
            ]],

            // --- Kids ---
            ['little-field-guides', 'Little Field Guides', 'New for young explorers', 'Curious children discover the plants, insects and wildlife living just beyond their classroom windows.', 'Kids', 'watch-kids.png', 2026, 'G', false, [
                ['Life on a Lily Pad', 14, 'Meet the tiny aquatic neighbours inhabiting a village pond.'],
                ['The Busy Banyan', 13, 'A single ancient tree becomes a bustling haven for hundreds of species.'],
                ['After the Rain', 15, 'Young nature explorers follow the clues left by fresh monsoon showers.'],
            ]],
        ];

        $showsBn = [
            'the-last-transmission' => ['শেষ সম্প্রচার', 'নতুন মৌলিক নাটক', '১৯৭১ সালের শেষ সপ্তাহে একটি বেতারকেন্দ্রে এক তরুণ সম্প্রচারক আবিষ্কার করে—সঠিকভাবে বেছে নেওয়া একটি বার্তা ভয়কেও অতিক্রম করতে পারে।'],
            'chhaya-shikari' => ['ছায়া শিকারী', 'ক্রাইম থ্রিলার সিরিজ', 'এক মেধাবী সাইবার গোয়েন্দা ও বন্দর পরিদর্শক উপকূলীয় এলাকায় সক্রিয় আন্তর্জাতিক চোরাচালান চক্রের রহস্য উন্মোচন করেন।'],
            'shukhi-shongshar' => ['সুখী সংসার', 'জনপ্রিয় পারিবারিক ধারাবাহিক', 'নদীমাতৃক এক যৌথ পরিবারের প্রজন্মের পর প্রজন্মের ভালোবাসা, মান-অভিমান ও সাংস্কৃতিক ঐতিহ্যের গল্প।'],
            'bhoot-shonibar' => ['ভূত শনিবার', 'মধ্যরাতের ভৌতিক কাহিনী', 'মধ্যরাতে বেতারের স্টুডিও থেকে সরাসরি পঠিত সারা দেশের শ্রোতাদের পাঠানো সত্য ভৌতিক ও অলৌকিক অভিজ্ঞতার গল্প।'],
            'rivers-that-remember' => ['স্মৃতিবাহী নদী', 'প্রামাণ্যচিত্র সিরিজ', 'বাংলাদেশের পরিবর্তনশীল জলপথ ঘিরে নৌকা সম্প্রদায়ের গল্প, জীবিকা ও গানের সঙ্গে ভ্রমণ করুন।'],
            'voices-of-betar' => ['বেতারের কণ্ঠ', 'আর্কাইভ প্রামাণ্যচিত্র', 'উপস্থাপক, প্রকৌশলী ও শিল্পীরা জনজীবনের অংশ হয়ে ওঠা বেতারের স্মরণীয় ঐতিহাসিক মুহূর্তগুলো ফিরে দেখেন।'],
            'tomorrows-builders' => ['আগামীর নির্মাতা', 'তথ্যভিত্তিক সিরিজ', 'তরুণ শিক্ষার্থী উদ্ভাবকেরা শ্রেণিকক্ষের বিজ্ঞান ধারণাকে সমাজের ব্যবহারিক কৃষি ও রোবটিক্স সরঞ্জামে রূপ দেন।'],
            'ready-together' => ['একসঙ্গে প্রস্তুত', 'মানুষের গল্প', 'দুর্যোগের আগে স্থানীয় সক্ষমতা ও সচেতনতা বাড়ানো স্বেচ্ছাসেবকদের অনুপ্রেরণাদায়ী গল্প।'],
            'archeology-of-mahasthangarh' => ['মহাস্থানগড়ের প্রতিধ্বনি', 'ঐতিহাসিক প্রত্নতত্ত্ব প্রামাণ্যচিত্র', 'করতোয়া নদীর তীরে অবস্থিত আড়াই হাজার বছরের প্রাচীন নগর সভ্যতার দুর্লভ প্রত্নতাত্ত্বিক নিদর্শন।'],
            'monsoon-kitchen' => ['বর্ষার রান্নাঘর', 'খাবার ও সংস্কৃতি', 'ঘরের রাঁধুনিরা মৌসুমি বর্ষার সুস্বাদু রেসিপি ও প্রজন্ম ধরে বহমান পারিবারিক রান্নার ঐতিহ্য ভাগ করে নেন।'],
            'songs-of-the-courtyard' => ['উঠানের গান', 'সরাসরি পরিবেশনা', 'দেশের শীর্ষ শাস্ত্রীয় ও লোকশিল্পীদের পরিবেশনায় রাগসংগীত ও মাটির গানের অন্তরঙ্গ সন্ধ্যা।'],
            'festivals-of-the-delta' => ['নদীমাতৃক বাংলার উৎসব', 'ঐতিহ্যবাহী সাংস্কৃতিক সিরিজ', 'নৌকাবাইচ, নবান্ন উৎসব ও মেলা নিয়ে বাংলার চিরন্তন আনন্দধারার অনন্য প্রামাণ্যচিত্র।'],
            'bhalobashar-koutuk' => ['ভালোবাসার কৌতুক', 'হাস্যরসাত্মক নাটক', 'গ্রামের এক ঘটকের ভুল বোঝাবুঝি এবং বিয়ে নিয়ে তৈরি মজার নাট্য পরিবেশনা।'],
            'gramer-hasir-golpo' => ['গ্রামের হাসির গল্প', 'গ্রামীণ রম্য ও নাটক', 'চায়ের দোকানের আড্ডা ও গ্রামের বুদ্ধিমান মানুষদের প্রতিদিনের মজার ঘটনার নাট্যরূপ।'],
            'raater-chhaya' => ['রাতের ছায়া', 'রহস্য ও ভৌতিক ধারাবাহিক', 'সিলেটের চা বাগানের গভীর রাতে ঘটে যাওয়া অলৌকিক ঘটনা অনুসন্ধানে এক সাংবাদিকের অভিযান।'],
            'nodi-kuler-pretopuri' => ['নদীকূলের প্রেতপুরী', 'ভৌতিক রহস্যগাথা', 'নদীতে ডুবে যাওয়া প্রাচীন জাহাজের রহস্য উদঘাটনে গিয়ে ঘটে যাওয়া ভুতুড়ে অভিজ্ঞতা।'],
            'betar-shongbad-bortika' => ['বেতার সংবাদ বর্তিকা', 'সাপ্তাহিক রাজনৈতিক ও অর্থনৈতিক বিশ্লেষণ', 'সপ্তাহের প্রধান প্রধান জাতীয় ও আন্তর্জাতিক খবরের গভীর বিশ্লেষণ নিয়ে বিশেষ পর্যালোচনা।'],
            'mukhomukhi-bortoman' => ['মুখোমুখি বর্তমান', 'অনুসন্ধানী সাক্ষাৎকার অনুষ্ঠান', 'জনগুরুত্বপূর্ণ নানা বিষয়ে মন্ত্রী ও নীতিনির্ধারকদের সাথে সরাসরি প্রশ্নোত্তর।'],
            'raater-shongket' => ['রাতের সংকেত', 'গোয়েন্দা ধারাবাহিক নাটক', 'ফরেনসিক বিজ্ঞানের সহায়তায় সিআইডির বিশেষ দলের রোমহর্ষক মামলার সমাধান।'],
            'shongshoy-o-shotti' => ['সংশয় ও সত্য', 'আদালতকেন্দ্রিক থ্রিলার', 'এক তরুণ সৎ প্রকৌশলীর বিরুদ্ধে আনা মিথ্যা অভিযোগের বিরুদ্ধে আইনজীবীর আইনি লড়াই।'],
            'shurjer-shondhane' => ['সূর্যের সন্ধানে', 'পুরস্কারপ্রাপ্ত পূর্ণদৈর্ঘ্য চলচ্চিত্র', 'উপকূলের মানুষের প্রতিকূলতার বিরুদ্ধে লড়াই ও টিকে থাকার মহাকাব্যিক সিনেমা।'],
            'nodir-naam-madhumoti' => ['নদীর নাম মধুমতী', 'কালজয়ী মুক্তিযুদ্ধভিত্তিক সিনেমা', 'মধুমতী নদীর তীরে দেশপ্রেম ও আত্মত্যাগের অবিস্মরণীয় মুক্তিযুদ্ধের কাহিনী।'],
            'ekti-notun-bhor' => ['একটি নতুন ভোর', 'স্বল্পদৈর্ঘ্য চলচ্চিত্র', 'এক কিশোরের বাঁশির সুরে পাড়া-প্রতিবেশীর বিরোধ মিটে যাওয়ার মানবিক গল্প।'],
            'stationer-chheleti' => ['স্টেশনের ছেলেটি', 'অনুপ্রেরণাদায়ী শর্ট ফিল্ম', 'অদম্য ইচ্ছাশক্তির জোরে রেলওয়ে স্টেশনের হকার থেকে বিশ্ববিদ্যালয়ে সুযোগ পাওয়া এক কিশোরের জীবনযুদ্ধ।'],
            'desher-gaan-o-shur' => ['দেশের গান ও সুর', 'জাতীয় ঐকতান পরিবেশনা', 'বেতারের সিম্ফনি অর্কেস্ট্রা এবং দেশের শীর্ষ শিল্পীদের কালজয়ী দেশাত্মবোধক গান।'],
            'baul-gaan-acoustic-sessions' => ['বাউল গান: নদীপারের সুর', 'অ্যাকোস্টিক লাইভ সেশন', 'কুষ্টিয়ার পদ্মাপারে লালন ও হাছন রাজার অমর বাণীর মরমী লাইভ পরিবেশনা।'],
            'little-field-guides' => ['ছোট্ট প্রকৃতি নির্দেশিকা', 'কিশোর অভিযাত্রীদের নতুন আয়োজন', 'কৌতূহলী শিশুরা শ্রেণিকক্ষের বাইরের গাছপালা, পোকামাকড় ও বন্যপ্রাণী আবিষ্কার করে।'],
        ];

        $episodeBn = [
            'The Signal' => 'সংকেত', 'Between Frequencies' => 'তরঙ্গের মাঝে', 'The Last Transmission' => 'শেষ সম্প্রচার',
            'Midnight Cargo' => 'মধ্যরাতের কার্গো', 'Encrypted Waters' => 'এনক্রিপ্টেড জলপথ', 'The Final Trap' => 'চূড়ান্ত ফাঁদ',
            'The Family Feast' => 'পারিবারিক ভোজ', 'New Beginnings' => 'নতুন সূচনা', 'Bridges of Understanding' => 'বোঝাপড়ার মেলবন্ধন',
            'The Abandoned Zamindar Bari' => 'পরিত্যক্ত জমিদার বাড়ি', 'Whispers in the Fog' => 'কুয়াশায় ফিসফিস', 'The Red Trunk' => 'লাল সিন্দুক',
            'Morning Tide' => 'সকালের জোয়ার', 'Moving Banks' => 'বদলে যাওয়া তীর', 'Songs Downstream' => 'ভাটির গান',
            'Behind the Microphone' => 'মাইক্রোফোনের পেছনে', 'Frequencies of Freedom' => 'স্বাধীনতার তরঙ্গ',
            'Small Machines, Big Ideas' => 'ছোট যন্ত্র, বড় ভাবনা', 'Solar on the Water' => 'জলের ওপর সৌরশক্তি',
            'The Shelter Team' => 'আশ্রয়কেন্দ্র দল', 'After the Storm' => 'ঝড়ের পরে', 'Layers of Time' => 'সময়ের স্তর',
            'First Rain Delicacies' => 'প্রথম বৃষ্টির খাবার', 'Hilsa and Mustard Dreams' => 'সরিষা ইলিশের স্বাদ',
            'Folk Roads' => 'লোকগানের পথ', 'Poetry in Raga' => 'রাগে কবিতা',
            'The Great Boat Race' => 'ঐতিহাসিক নৌকাবাইচ', 'Harvest Lanterns' => 'নবান্নের প্রদীপ',
            'The Letter Mix-up' => 'চিঠির গোলমাল', 'The Fake Astrologer' => 'ভুয়া জ্যোতিষী',
            'The Great Tea Debate' => 'চায়ের কাপে ঝড়', 'The Modern Bicycle' => 'স্মার্ট সাইকেল',
            'The Ghost of Estate 7' => 'সাত নম্বর এস্টেটের আত্মা', 'The Vanishing Trail' => 'অদৃশ্য পদচিহ্ন',
            'Under the Dark Water' => 'কালো জলের নিচে', 'The Economic Horizon' => 'অর্থনীতির দিগন্ত',
            'Diplomacy in Focus' => 'কূটনৈতিক পর্যালোচনা', 'Digital Governance Dialogue' => 'ডিজিটাল সুশাসন সংলাপ',
            'The Silent Witness' => 'নীরব সাক্ষী', 'Digital Shadows' => 'ডিজিটাল ছায়া',
            'The Blueprint' => 'ব্লুপ্রিন্ট রহস্য', 'Full Feature Movie' => 'সম্পূর্ণ পূর্ণদৈর্ঘ্য সিনেমা',
            'Full Feature Presentation' => 'মূল চলচ্চিত্র পরিবেশনা', 'Short Film' => 'স্বল্পদৈর্ঘ্য চলচ্চিত্র',
            'Complete Short Film' => 'পুরো শর্ট ফিল্ম', 'Songs of the Motherland' => 'মাতৃভূমির গান',
            'Rhythms of Freedom' => 'স্বাধীনতার ছন্দ', 'Mon Amar Moner Moto' => 'মন আমার মনের মতো',
            'Nodi Bhora Dheu' => 'নদী ভরা ঢেউ', 'Life on a Lily Pad' => 'শাপলা পাতার জীবন',
            'The Busy Banyan' => 'ব্যস্ত বটগাছ', 'After the Rain' => 'বৃষ্টির পরে',
        ];

        foreach ($shows as $position => [$slug, $title, $eyebrow, $description, $category, $image, $year, $rating, $isFeatured, $episodes]) {
            [$titleBn, $eyebrowBn, $descriptionBn] = $showsBn[$slug];
            $categoryId = WatchCategory::query()->where('name', $category)->value('id');
            $show = WatchShow::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'watch_category_id' => $categoryId,
                'title' => $title,
                'title_bn' => $titleBn,
                'eyebrow' => $eyebrow,
                'eyebrow_bn' => $eyebrowBn,
                'description' => $description,
                'description_bn' => $descriptionBn,
                'category' => $category,
                'image_path' => 'portal/demo/'.$image,
                'year' => $year,
                'rating' => $rating,
                'position' => $position,
                'is_featured' => $isFeatured,
                'is_published' => true,
                'published_at' => now()->subDays($position + 1),
            ]);

            foreach ($episodes as $episodePosition => [$episodeTitle, $duration, $episodeDescription]) {
                $show->episodes()->updateOrCreate(['title' => $episodeTitle], [
                    'title_bn' => $episodeBn[$episodeTitle] ?? null,
                    'description' => $episodeDescription,
                    'description_bn' => 'এই পর্বে '.($episodeBn[$episodeTitle] ?? $episodeTitle).' বিষয়টি তুলে ধরা হয়েছে।',
                    'duration_minutes' => $duration,
                    'position' => $episodePosition + 1,
                    'is_published' => true,
                ]);
            }
        }

        // --- Seed Watch Clips (Shorts feed) ---
        $clips = [
            [
                'title' => 'Rare Historic Recording from Swadhin Bangla Betar Kendra Studio (1971)',
                'title_bn' => 'স্বাধীন বাংলা বেতার কেন্দ্রের ঐতিহাসিক দুর্লভ স্টুডিও রেকর্ডিং (১৯৭১)',
                'description' => 'Original wartime broadcast audio reel preserved at the national Betar sound archive.',
                'description_bn' => 'জাতীয় বেতার শব্দ আর্কাইভে সংরক্ষিত মুক্তিযুদ্ধকালীন মূল সম্প্রচারিত অডিও রিল।',
                'slug' => 'historic-swadhin-bangla-betar-1971',
                'creator_name' => 'Bangladesh Betar Archive',
                'creator_handle' => '@bangladeshbetar',
                'thumbnail_path' => 'portal/demo/watch-hero.png',
                'audio_track' => 'Original Radio Relay (1971) · Swadhin Bangla Betar',
                'hashtags' => '#History, #1971, #BetarArchive, #Shorts',
                'likes_count' => 14820,
                'dislikes_count' => 42,
                'position' => 1,
            ],
            [
                'title' => 'Acoustic Bhatiyali River Song Live at Sunset on the Meghna',
                'title_bn' => 'মেঘনার মোহনায় সূর্যাস্তের মনোরম ভাটিয়ালি গানের সুর',
                'description' => 'Soulful river folk melody performed live by traditional Baul singers on a country boat.',
                'description_bn' => 'গ্রামীণ নৌকায় ঐতিহ্যবাহী বাউল শিল্পীদের সরাসরি পরিবেশনায় মন জুড়ানো ভাটিয়ালি গান।',
                'slug' => 'acoustic-bhatiyali-river-song',
                'creator_name' => 'Betar Folk Hub',
                'creator_handle' => '@betarfolk',
                'thumbnail_path' => 'portal/demo/watch-river.png',
                'audio_track' => 'Nodi Bhora Dheu · Baul Acoustic Live',
                'hashtags' => '#FolkMusic, #RiverLife, #Baul, #Shorts',
                'likes_count' => 8940,
                'dislikes_count' => 19,
                'position' => 2,
            ],
            [
                'title' => 'Behind the Scenes: How Foley Voice Artists Create Thunder in Radio Drama',
                'title_bn' => 'বেতার নাটকের নেপথ্যে: কীভাবে তৈরি হয় বজ্রপাত ও বৃষ্টির শব্দ?',
                'description' => 'Foley artists at Studio 4 demonstrate live acoustic sound effect techniques using metal sheets and water tubs.',
                'description_bn' => 'স্টুডিও ৪-এর শব্দশিল্পীরা টিনের পাত ও জলের পাত্র ব্যবহার করে নিখুঁত বৃষ্টির শব্দ তৈরির কৌশল দেখাচ্ছেন।',
                'slug' => 'foley-voice-artists-thunder-radio-drama',
                'creator_name' => 'Drama Studio 4',
                'creator_handle' => '@betardrama',
                'thumbnail_path' => 'portal/demo/news-tech.png',
                'audio_track' => 'Original Studio Foley Sound Effects',
                'hashtags' => '#BehindTheScenes, #Foley, #RadioDrama, #Shorts',
                'likes_count' => 12350,
                'dislikes_count' => 58,
                'position' => 3,
            ],
            [
                'title' => 'Midnight Bhoot Shonibar Listener Story Teaser: The Haunted Tea Estate',
                'title_bn' => 'ভূত শনিবারের গা শিউরে ওঠা সত্য ঘটনা: চা বাগানের গভীর রাতে',
                'description' => 'A chilling excerpt from midnight broadcast recounting an unexplained phenomenon in Sreemangal tea hills.',
                'description_bn' => 'শ্রীমঙ্গলের চা বাগানের গভীর রাতের এক রহস্যময় ও রোমাঞ্চকর অলৌকিক অভিজ্ঞতার অংশবিশেষ।',
                'slug' => 'bhoot-shonibar-haunted-tea-estate',
                'creator_name' => 'Bhoot Shonibar Official',
                'creator_handle' => '@bhootshonibar',
                'thumbnail_path' => 'portal/demo/watch-hero.png',
                'audio_track' => 'Midnight Radio Eerie Atmosphere Theme',
                'hashtags' => '#BhootShonibar, #Horror, #Supernatural, #Shorts',
                'likes_count' => 24700,
                'dislikes_count' => 110,
                'position' => 4,
            ],
            [
                'title' => 'Young Student Inventors Build Floating Solar Pumps for Rural Farmers',
                'title_bn' => 'তরুণ শিক্ষার্থীদের তৈরি জলের ওপর ভাসমান সৌরবিদ্যুৎ চালিত পাম্প',
                'description' => 'BUET engineering students introduce low-cost eco-friendly agricultural irrigation solutions.',
                'description_bn' => 'বুয়েটের প্রকৌশল শিক্ষার্থীদের উদ্ভাবিত সাশ্রয়ী ও পরিবেশবান্ধব কৃষি সেচ সমাধান।',
                'slug' => 'floating-solar-pumps-rural-farmers',
                'creator_name' => 'Innovators of Bangladesh',
                'creator_handle' => '@innovatorsbd',
                'thumbnail_path' => 'portal/demo/news-rice.png',
                'audio_track' => "Tomorrow's Builders Tech Beat",
                'hashtags' => '#Innovation, #Solar, #GreenTech, #Shorts',
                'likes_count' => 6730,
                'dislikes_count' => 15,
                'position' => 5,
            ],
            [
                'title' => 'Traditional Monsoon Ilish Cooking Secret in Under 60 Seconds',
                'title_bn' => '৬০ সেকেন্ডে বর্ষার ঐতিহ্যবাহী সরিষা ইলিশ রান্নার গোপন রেসিপি',
                'description' => 'Culinary masterclass exploring authentic mustard Hilsa preparations from Chandpur.',
                'description_bn' => 'চাঁদপুরের ঐতিহ্যবাহী খাঁটি সরিষা ইলিশ রান্নার নিখুঁত রেসিপি।',
                'slug' => 'monsoon-ilish-cooking-secret',
                'creator_name' => 'The Monsoon Kitchen',
                'creator_handle' => '@monsoonkitchen',
                'thumbnail_path' => 'portal/demo/news-coast.png',
                'audio_track' => 'Rustic Kitchen Beats · Folk Rhythms',
                'hashtags' => '#Food, #Ilish, #BengaliCuisine, #Shorts',
                'likes_count' => 18900,
                'dislikes_count' => 84,
                'position' => 6,
            ],
            [
                'title' => 'Master Sitarist Performs Raga Megh with National Symphony',
                'title_bn' => 'জাতীয় সিম্ফনির সাথে রাগ মেঘের অনবদ্য সেতার পরিবেশনা',
                'description' => 'Hypnotic classical Indian instrumental rendition welcoming the arrival of monsoon rain.',
                'description_bn' => 'বর্ষা বন্দনায় বাংলাদেশ বেতার জাতীয় সিম্ফনি অর্কেস্ট্রার সাথে রাগ মেঘের পরিবেশনা।',
                'slug' => 'master-sitarist-raga-megh',
                'creator_name' => 'Betar Classical Music',
                'creator_handle' => '@betarclassical',
                'thumbnail_path' => 'portal/demo/watch-music.png',
                'audio_track' => 'Raga Megh · Classical Sitar Ensemble',
                'hashtags' => '#ClassicalMusic, #Sitar, #Raga, #Shorts',
                'likes_count' => 11240,
                'dislikes_count' => 31,
                'position' => 7,
            ],
            [
                'title' => '2,500-Year-Old Terracotta Artifacts Unearthed at Mahasthangarh',
                'title_bn' => 'মহাস্থানগড়ে উন্মোচিত আড়াই হাজার বছরের প্রাচীন পোড়ামাটির নিদর্শন',
                'description' => 'Archaeologists unveil extraordinary ancient Maurya-era architectural terracotta seals in Bogura.',
                'description_bn' => 'বগুড়ার মহাস্থানগড়ে আবিষ্কৃত মৌর্য যুগের বিরল পোড়ামাটির সিলমোহর ও প্রত্নসম্পদ।',
                'slug' => 'terracotta-artifacts-mahasthangarh',
                'creator_name' => 'Heritage Bangladesh',
                'creator_handle' => '@heritagebd',
                'thumbnail_path' => 'portal/demo/watch-river.png',
                'audio_track' => 'Echoes of Antiquity · Historical Theme',
                'hashtags' => '#Archaeology, #Heritage, #Mahasthangarh, #Shorts',
                'likes_count' => 9540,
                'dislikes_count' => 22,
                'position' => 8,
            ],
        ];

        foreach ($clips as $clipData) {
            \App\Models\WatchClip::query()->updateOrCreate(['slug' => $clipData['slug']], [
                'title' => $clipData['title'],
                'title_bn' => $clipData['title_bn'],
                'description' => $clipData['description'],
                'description_bn' => $clipData['description_bn'],
                'creator_name' => $clipData['creator_name'],
                'creator_handle' => $clipData['creator_handle'],
                'thumbnail_path' => $clipData['thumbnail_path'],
                'audio_track' => $clipData['audio_track'],
                'hashtags' => $clipData['hashtags'],
                'likes_count' => $clipData['likes_count'],
                'dislikes_count' => $clipData['dislikes_count'],
                'position' => $clipData['position'],
                'is_published' => true,
                'published_at' => now()->subDays($clipData['position']),
            ]);
        }

        $this->command?->info('News and Watch portal demo content seeded.');
    }

    private function installImages(): void
    {
        $disk = Storage::disk('public');
        $sourceDirectory = base_path('database/seeders/assets/portal');

        foreach (File::files($sourceDirectory) as $file) {
            $contents = File::get($file->getPathname());
            if ($contents === '') {
                throw new RuntimeException('Portal demo image is empty: '.$file->getFilename());
            }

            $disk->put('portal/demo/'.$file->getFilename(), $contents, 'public');
        }
    }
}
