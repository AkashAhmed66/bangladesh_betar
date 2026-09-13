<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\User;
use App\Models\WatchCategory;
use App\Models\WatchClip;
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
            // ==========================================
            // --- BANGLADESH: NATIONAL (bangladesh-national) ---
            // ==========================================
            ['rail-link-connects-river-regions', 'bangladesh-national', 'New rail link brings river communities closer to the capital', 'The expanded route is expected to shorten journeys, improve regional trade and give more passengers access to reliable public transport.', 'news-hero.png', 4, 12, 7820, true, [
                "A newly expanded rail connection has begun carrying passengers across one of the country's busiest river corridors, creating a faster link between regional towns and Dhaka.",
                'Transport planners say the service is designed to reduce road pressure while making education, healthcare and markets easier to reach. More services are expected to be added after the first operating review.',
                'Local businesses welcomed the opening and said predictable journey times could make it easier to move fresh produce and small manufactured goods between districts.',
            ]],
            ['coastal-volunteers-complete-shelter-drill', 'bangladesh-national', 'Coastal volunteers complete early-season shelter drill', 'Community teams checked first-aid supplies, evacuation routes and communications before the next period of severe weather.', 'news-coast.png', 5, 48, 3240, false, [
                'Volunteer groups in coastal communities have completed a coordinated readiness exercise focused on cyclone shelter access and household communication.',
                'Teams inspected emergency supplies and practised supporting older residents, children and people with disabilities during an evacuation.',
                'Organisers said the exercise will be repeated in remote areas where travel becomes difficult during heavy rain.',
            ]],
            ['national-universal-pension-enrollment-surpasses-target', 'bangladesh-national', 'National Universal Pension Scheme surpasses registration milestones', 'Over two million citizens across self-employed, expatriate and informal sectors enroll in digitally managed retirement safety funds.', 'news-hero.png', 4, 130, 6890, true, [
                'The National Pension Authority has recorded an unprecedented surge in registrations under the flagship Universal Pension Scheme across all four public categories.',
                'Expatriate wage earners and rural informal sector workers have expressed strong confidence in the transparent, automated smartphone contribution gateway.',
                'Finance officials confirmed that monthly pension disbursements from matured initial schemes will begin smoothly through direct electronic fund transfers.',
            ]],
            ['padma-river-bank-embankment-fortification-completed', 'bangladesh-national', 'Padma riverbank geo-bag fortification project completed ahead of monsoon', 'Engineers deploy multi-layered geotextile revetments protecting thousands of riverside homesteads and fertile farmland.', 'watch-river.png', 4, 380, 2940, false, [
                'Water Development Board engineers have completed critical embankment reinforcement works across vulnerable stretches of the lower Padma basin.',
                'The modern revetment technique uses heavy-duty sand-filled geotextile bags and stone pitching to dissipate powerful monsoon water currents.',
                'Local Union Parishad leaders noted that the permanent riverbank protection brings peace of mind to farming communities facing annual erosion risks.',
            ]],

            // ==========================================
            // --- BANGLADESH: DHAKA (bangladesh-dhaka) ---
            // ==========================================
            ['dhaka-metro-expands-evening-schedule', 'bangladesh-dhaka', 'Dhaka Metro expands evening schedule to meet commuter demand', 'Additional train frequency and extended terminal operating hours ease rush-hour congestion across key city corridors.', 'news-hero.png', 3, 90, 8910, true, [
                'Dhaka Mass Transit authorities have added new evening services to cater to rising commuter numbers returning from commercial hubs.',
                'Passenger feedback highlighted significant reductions in station waiting times and improved interchange convenience.',
                'Feeder bus routes are being synchronized with metro arrivals to provide seamless last-mile connectivity for urban travelers.',
            ]],
            ['dhaka-circular-waterway-launches-modern-eco-catamarans', 'bangladesh-dhaka', 'Dhaka circular waterway launches modern electric and hybrid catamarans', 'Water transport authority introduces zero-emission water buses connecting Sadarghat, Gabtoli and Ashulia terminals.', 'watch-river.png', 4, 210, 4350, false, [
                'The Inland Water Transport Authority has commissioned a new fleet of modern lightweight catamarans along the Buriganga and Turag river routes.',
                'Commuters can now bypass gridlocked arterial roads, enjoying comfortable, Wi-Fi-enabled river transits between north and south Dhaka.',
                'Transport planners highlighted that sustainable waterway transit will reduce vehicular emissions and revive the historic riverine charm of the capital.',
            ]],
            ['dhaka-smart-traffic-signal-corridor-eases-commuting', 'bangladesh-dhaka', 'AI-assisted adaptive traffic management corridor inaugurated in Dhaka', 'Smart cameras and real-time vehicular density sensors dynamically optimize green signal timings across major intersections.', 'news-tech.png', 4, 520, 3780, false, [
                'Dhaka North and South City Corporations have jointly activated an artificial intelligence-driven traffic signal network across key thoroughfares.',
                'The adaptive system continuously monitors vehicle queues and automatically prioritizes emergency ambulances and high-capacity public buses.',
                'Traffic police reported a notable twenty-five percent decrease in peak-hour intersection delays during the initial demonstration trials.',
            ]],

            // ==========================================
            // --- BANGLADESH: CHATTOGRAM (bangladesh-chattogram) ---
            // ==========================================
            ['chattogram-port-sets-record-container-throughput', 'bangladesh-chattogram', 'Chattogram port sets record container throughput after digital upgrades', 'Automated gate clearances and smart yard tracking cut vessel turnaround times by over twenty percent.', 'watch-river.png', 4, 720, 2450, false, [
                'Chattogram Port Authority reported record cargo handling metrics following the rollout of automated terminal management software.',
                'Importers and shipping lines commended the streamlined customs integrations and round-the-clock clearance windows.',
                'Port officials affirmed ongoing investments in deep-draft berths to accommodate next-generation container carriers.',
            ]],
            ['karnaphuli-industrial-economic-zone-attracts-foreign-tech', 'bangladesh-chattogram', 'Karnaphuli Economic Zone attracts high-tech manufacturing investments', 'Modern industrial infrastructure in Anwara draws international electronics and solar panel assembly plants.', 'news-tech.png', 4, 310, 3620, false, [
                'The Bangladesh Economic Zones Authority has approved five major multinational joint ventures within the Karnaphuli Special Economic Zone.',
                'Enhanced connectivity via the Bangabandhu Tunnel allows seamless raw material imports from port terminals directly to factory floors.',
                'The new facilities are projected to generate over twenty thousand skilled engineering and technician jobs for the regional workforce.',
            ]],
            ['coxs-bazar-railway-expands-scenic-express-services', 'bangladesh-chattogram', 'Chattogram-Cox’s Bazar tourist express trains add panoramic vistadome coaches', 'Travellers enjoy breathtaking coastal forest and hill views along South Asia’s newest scenic rail corridor.', 'news-hero.png', 3, 640, 5120, true, [
                'Bangladesh Railway has introduced luxury panoramic coaches on the popular route connecting Chattogram port city with Cox’s Bazar beach terminal.',
                'Passengers praise the wide observation windows and onboard audio commentary highlighting the biodiversity of the Chunati Wildlife Sanctuary.',
                'Hospitality operators along the southeast coast reported a surge in weekend tourism bookings following the train schedule expansion.',
            ]],

            // ==========================================
            // --- BANGLADESH: SYLHET (bangladesh-sylhet) ---
            // ==========================================
            ['sylhet-tea-estates-adopt-solar-irrigation', 'bangladesh-sylhet', 'Sylhet tea estates adopt solar-powered micro-irrigation systems', 'Greener energy solutions protect fragile mountain ecosystems while sustaining premium tea output through dry spells.', 'news-rice.png', 4, 340, 1980, false, [
                'Several heritage tea plantations in the Sylhet region have installed solar-powered micro-sprinkler networks across high-elevation slopes.',
                'Plantation managers noted that consistent moisture delivery prevented leaf scorch during recent unseasonal dry spells.',
                'The initiative also reduces reliance on diesel pumps, lowering operational emissions and water runoff into nearby stream beds.',
            ]],
            ['ratargul-swamp-forest-community-conservation-tours-expand', 'bangladesh-sylhet', 'Ratargul freshwater swamp forest launches community-led eco-guiding program', 'Local boatmen cooperatives implement sustainable tourism guidelines preserving rare submerged aquatic flora and bird sanctuaries.', 'watch-river.png', 4, 490, 2750, false, [
                'Forest officials and indigenous community elders in Gowainghat have established an organized eco-tourism protocol for Ratargul swamp forest.',
                'Visitors navigate the freshwater canopy exclusively on silent, paddle-powered wooden country boats to prevent disturbing nesting wildlife.',
                'Trained youth guides provide ecological education while entrance revenue directly funds community conservation nurseries and tree planting.',
            ]],
            ['haor-flood-resilient-raised-homestead-clusters-inaugurated', 'bangladesh-sylhet', 'Flood-resilient raised homestead clusters protect Sunamganj haor communities', 'Engineered earthen mounds with wave-protection barriers keep isolated wetland villages safe during flash floods.', 'news-coast.png', 4, 820, 3180, false, [
                'Disaster preparedness agencies have inaugurated twenty elevated village cluster platforms across the vast wetland haors of Sunamganj.',
                'Each raised settlement features reinforced concrete wave-breaking retaining walls, solar micro-grids, and deep safe drinking water tube-wells.',
                'Haor residents celebrated the security of knowing their homes, livestock and harvested boro grain remain dry during seasonal monsoon swells.',
            ]],

            // ==========================================
            // --- POLITICS: PARLIAMENT (politics-parliament) ---
            // ==========================================
            ['parliament-passes-digital-public-service-reform-bill', 'politics-parliament', 'Parliament passes comprehensive digital public service reform bill', 'New legislation establishes citizen service guarantees, automated grievance redressal and unified data interoperability.', 'news-hero.png', 5, 25, 6420, true, [
                'The National Parliament has unanimously passed landmark legislation modernizing government-to-citizen digital delivery standards.',
                'Under the new framework, essential civil certificates, land registry extracts, and utility permits must meet strict statutory response times.',
                'Lawmakers across party lines praised the inclusion of strong privacy safeguards and open accountability dashboards for district administration.',
            ]],
            ['all-party-climate-caucus-calls-for-delta-funding', 'politics-parliament', 'All-party parliamentary caucus calls for accelerated delta resilience funding', 'Lawmakers urge bilateral and global multilateral partners to prioritize grants over loans for river protection projects.', 'news-coast.png', 5, 860, 3190, true, [
                'Members of the Parliamentary Climate Caucus released a joint declaration advocating increased sovereign grants for river embankment fortifications.',
                'The declaration underscores Bangladesh’s pioneering role in adaptation planning and emphasizes the urgency of protecting vulnerable coastal constituencies.',
                'A delegation of lawmakers is scheduled to present the resolution at upcoming international climate finance roundtables.',
            ]],
            ['parliamentary-standing-committee-reviews-higher-education-budget', 'politics-parliament', 'Parliamentary committee recommends increased funding for university research and STEM labs', 'Lawmakers advocate targeted endowments for robotics, biotechnology and climate adaptation innovation in public universities.', 'news-tech.png', 4, 430, 2870, false, [
                'The Parliamentary Standing Committee on the Ministry of Education held an extensive review on modernizing public university curricula.',
                'Committee members stressed the need to connect university research laboratories directly with national industrial and agricultural challenges.',
                'The panel proposed establishing competitive national innovation grants to encourage young faculty and graduate student patents.',
            ]],

            // ==========================================
            // --- POLITICS: ELECTIONS (politics-elections) ---
            // ==========================================
            ['election-commission-upgrades-biometric-voter-verification', 'politics-elections', 'Election Commission upgrades biometric voter verification infrastructure', 'Updated field devices enhance accuracy, accessibility and cybersecurity for upcoming nationwide local elections.', 'news-tech.png', 4, 160, 4110, false, [
                'The Election Commission has unveiled updated voter authentication kits designed for rapid verification in rural and urban polling centres.',
                'The new portable units feature improved offline encryption, high-resolution fingerprint sensors, and extended battery life for remote stations.',
                'Civic observers and political representatives attended public demonstration trials held across multiple division headquarters.',
            ]],
            ['delimitation-of-constituencies-finalized-with-gis-mapping', 'politics-elections', 'Election Commission finalizes electoral boundary maps using advanced GIS data', 'Satellite demographic mapping ensures balanced voter population distribution and administrative coherence.', 'news-hero.png', 4, 710, 3240, false, [
                'The Election Commission has concluded the periodic delimitation review of parliamentary constituencies using geospatial satellite data.',
                'The updated boundaries reflect recent census urbanization trends while preserving geographical compactness and communication links.',
                'Public gazette notifications and digitized district boundary maps have been made accessible online for citizen feedback and review.',
            ]],

            // ==========================================
            // --- POLITICS: GOVERNMENT (politics-government) ---
            // ==========================================
            ['local-government-dialogue-focuses-on-civic-transparency', 'politics-government', 'Local government dialogue focuses on civic budget transparency', 'Union Parishad leaders and community representatives review participatory planning mechanisms and municipal spending.', 'watch-hero.png', 4, 410, 1820, false, [
                'A national summit on grassroots governance convened municipal mayors and Union Parishad chairpersons to share participatory budgeting models.',
                'Civic leaders presented case studies where citizen open-floor forums shaped priority allocations for village roads, drainage, and street lighting.',
                'The ministry affirmed plans to tie annual development incentive grants to verified community engagement metrics.',
            ]],
            ['public-administration-adopts-paperless-smart-governance-cloud', 'politics-government', 'Ministries transition to sovereign paperless e-governance cloud system', 'Unified digital file processing cuts official approval turnaround times and strengthens administrative traceability.', 'news-tech.png', 4, 280, 3960, false, [
                'The Ministry of Public Administration has mandated the rollout of the upgraded sovereign e-Nothi digital document management system across all directorates.',
                'Civil servants can now review, annotate, and digitally sign policy files securely from official encrypted mobile workstations.',
                'The transition has already conserved millions of sheets of paper and significantly reduced administrative processing bottlenecks.',
            ]],

            // ==========================================
            // --- POLITICS: PARTIES (politics-parties) ---
            // ==========================================
            ['civic-forums-engage-political-leaders-on-youth-manifesto', 'politics-parties', 'Youth policy dialogue brings together leaders across political spectrum', 'University students and civic researchers present policy proposals on employment, digital rights and environmental sustainability.', 'news-hero.png', 4, 520, 2760, false, [
                'A non-partisan national youth summit hosted representatives from major political parties for a constructive debate on future national priorities.',
                'Youth delegates presented empirical research calling for greater investments in technical vocational hubs and mental healthcare access.',
                'Party spokespersons welcomed the constructive dialogue and pledged to integrate actionable youth recommendations into upcoming party policy platforms.',
            ]],

            // ==========================================
            // --- WORLD: ASIA (world-asia) ---
            // ==========================================
            ['river-research-maps-seasonal-change', 'world-asia', 'Researchers map how seasonal rivers are changing across borders', 'A new public dataset combines satellite observations with reports from people living beside major waterways.', 'watch-river.png', 6, 180, 4870, true, [
                'Researchers have released an open dataset showing how river channels and nearby settlements change across the seasons.',
                'The project combines satellite imagery with observations contributed by schools and community groups across regional riparian nations.',
                'Planners hope the information can support safer local infrastructure and better decisions about erosion-prone areas.',
            ]],
            ['bangladesh-japan-trade-pact-enters-final-review', 'world-asia', 'Bangladesh-Japan economic partnership agreement enters final review', 'Bilateral negotiations conclude on tariff reductions, technological cooperation and workforce skill exchanges.', 'news-hero.png', 4, 670, 3890, false, [
                'Trade delegations in Tokyo and Dhaka have completed technical consultations on the Comprehensive Economic Partnership Agreement.',
                'The pact aims to bolster industrial manufacturing investments, expand high-tech vocational exchanges, and eliminate duties on key exports.',
                'Commercial chambers in both nations expect the agreement to catalyse joint ventures in automotive parts and renewable energy hardware.',
            ]],
            ['south-asian-regional-power-grid-trials-begin', 'world-asia', 'South Asian regional clean energy transmission trials commence', 'Cross-border interconnection enables seasonal power sharing between Himalayan hydroelectric plants and coastal grids.', 'news-tech.png', 5, 1320, 2940, false, [
                'Power transmission entities have initiated synchronized test flows along the regional high-voltage cross-border transmission corridor.',
                'The trilateral arrangement allows excess summer hydropower from Nepal and Bhutan to feed peak industrial demand in Bangladesh.',
                'Energy analysts regard the milestone as a pivotal breakthrough for regional decarbonisation and cost-effective grid stability.',
            ]],
            ['asean-bangladesh-digital-trade-connectivity-forum-concludes', 'world-asia', 'ASEAN-Bangladesh trade forum explores semiconductor and digital supply chains', 'Delegates from Singapore, Malaysia and Vietnam discuss joint investments in electronics manufacturing and software engineering.', 'news-tech.png', 4, 380, 3420, false, [
                'High-level commercial delegations concluded a two-day regional economic conference in Dhaka focused on regional supply chain resilience.',
                'Discussions centered on establishing mutual recognition for cross-border digital signatures, fintech pipelines, and hardware testing standards.',
                'Regional trade bodies projected strong mutual growth as Bangladeshi tech firms expand cloud software services across Southeast Asian markets.',
            ]],

            // ==========================================
            // --- WORLD: EUROPE (world-europe) ---
            // ==========================================
            ['eu-approves-extended-gsp-plus-market-access-roadmap', 'world-europe', 'European Union and Bangladesh finalize GSP+ sustainable trade roadmap', 'Comprehensive agreements on labor standards, environmental compliance and circular fashion secure duty-free access.', 'news-hero.png', 4, 250, 4890, true, [
                'Trade officials in Brussels and Dhaka have reached consensus on the transition roadmap for the EU’s modernized GSP+ preferential trade scheme.',
                'The framework rewards Bangladesh’s rapid adoption of green garment manufacturing facilities and verified workplace safety protocols.',
                'European apparel retailers praised the agreement, noting that Bangladesh remains their top global destination for sustainable ethical fashion.',
            ]],
            ['scandinavian-clean-energy-consortium-funds-bay-wind-study', 'world-europe', 'Nordic clean energy consortium launches offshore wind feasibility study in Bay of Bengal', 'Meteorological data buoys collect wind velocity and marine ecosystem telemetry along the southern maritime boundary.', 'news-coast.png', 5, 780, 2640, false, [
                'A consortium of Scandinavian wind energy pioneers has partnered with the Power Division to map offshore wind energy potential in the Bay of Bengal.',
                'Preliminary acoustic radar and satellite anemometer readings indicate powerful, consistent sea breezes capable of generating thousands of megawatts.',
                'The project adheres to stringent marine environmental guidelines to ensure zero disruption to migratory dolphin and sea turtle breeding routes.',
            ]],

            // ==========================================
            // --- WORLD: AMERICAS (world-americas) ---
            // ==========================================
            ['un-climate-summit-agrees-on-new-loss-and-damage-facility', 'world-americas', 'UN climate summit agrees on operational rules for loss and damage facility', 'Vulnerable delta and island nations secure dedicated windows for urgent climate disaster rehabilitation funding.', 'news-coast.png', 5, 220, 6130, true, [
                'Delegates at international climate negotiations have finalised administrative guidelines to govern the loss and damage fund.',
                'The framework provides expedited disbursements to developing nations suffering acute losses from extreme weather events and sea-level rise.',
                'Bangladesh’s delegation was widely lauded for coordinating unified positions among least developed countries and small island states.',
            ]],
            ['north-american-universities-partner-with-bangladesh-on-ai', 'world-americas', 'Leading North American research universities launch collaborative AI labs in Dhaka', 'Joint research programs focus on Bengali natural language processing, predictive agriculture and remote healthcare diagnostics.', 'news-tech.png', 4, 590, 3180, false, [
                'Top-tier engineering institutes from the United States and Canada have signed cooperative research agreements with BUET and Dhaka University.',
                'The bilateral initiative provides fully funded doctoral fellowships and access to cloud supercomputing clusters for Bangladeshi researchers.',
                'Initial projects include developing speech recognition models for regional dialects and satellite machine learning algorithms for flood forecasting.',
            ]],

            // ==========================================
            // --- WORLD: MIDDLE EAST (world-middle-east) ---
            // ==========================================
            ['gulf-cooperation-council-expands-skilled-workforce-agreements', 'world-middle-east', 'Gulf nations expand skilled professional recruitment agreements with Bangladesh', 'Bilateral treaties open new career paths for certified nurses, software developers, and green construction engineers.', 'news-hero.png', 4, 340, 4210, false, [
                'The Ministry of Expatriates’ Welfare has signed expanded skill-recognition treaties with multiple Gulf Cooperation Council member states.',
                'Under the new framework, graduates from accredited domestic polytechnics and nursing colleges receive streamlined work visa processing and fair wage protections.',
                'Labor analysts highlighted that shifting toward specialized technical talent enhances remittance stability and worker career growth.',
            ]],
            ['saudi-bangladesh-solar-infrastructure-investment-signed', 'world-middle-east', 'Saudi energy conglomerate signs major utility-scale solar project in Chattogram', 'The clean power plant will feed hundreds of megawatts of renewable energy directly into the national transmission grid.', 'news-tech.png', 4, 890, 2760, false, [
                'A landmark foreign direct investment agreement was finalized in Riyadh for constructing a utility-scale solar photovoltaic park in Bangladesh.',
                'The facility will utilize high-efficiency bifacial solar panels and smart inverter substations designed for humid coastal atmospheric conditions.',
                'Project directors confirmed that commercial operations are slated to commence within eighteen months, bolstering national clean energy targets.',
            ]],

            // ==========================================
            // --- BUSINESS: ECONOMY (business-economy) ---
            // ==========================================
            ['aman-harvest-reaches-local-markets', 'business-economy', 'Strong Aman harvest begins reaching local markets', 'Farmers across several northern districts report healthy yields after a season of careful water management.', 'news-rice.png', 3, 35, 5420, true, [
                'Freshly harvested Aman rice is arriving at regional markets as growers complete work across the northern districts.',
                'Agriculture officers said local irrigation planning and timely field advice helped many farmers protect their crops through changing weather conditions.',
                'Market observers are monitoring transport and storage costs as the harvest moves from farms to mills and retail centres.',
            ]],
            ['leather-and-footwear-exports-post-double-digit-growth', 'business-economy', 'Leather and footwear exports post double-digit quarterly growth', 'Investments in green tannery certifications and eco-friendly compliance unlock fresh European and Asian buyer demand.', 'news-hero.png', 4, 280, 2780, false, [
                'Export figures from the Export Promotion Bureau showed robust growth in finished leather goods and premium athletic footwear shipments.',
                'Industry spokespersons attributed the surge to major upgrades in central effluent treatment systems and sustainable manufacturing protocols.',
                'Manufacturers are expanding vocational design academies to cultivate homegrown product stylists for international fashion houses.',
            ]],
            ['export-diversification-fund-boosts-electronics-manufacturing', 'business-economy', 'National Export Diversification Fund spurs electronics and home appliance manufacturing', 'Domestic consumer electronics brands expand high-value refrigerator, smart television and compressor exports across global markets.', 'news-tech.png', 4, 560, 3190, false, [
                'The Ministry of Commerce’s special export incentive program has catalysed significant investments in heavy industrial electronics manufacturing.',
                'State-of-the-art robotic production lines in Gazipur and Narsingdi are producing energy-efficient inverter appliances certified for global distribution.',
                'Industry leaders noted that value-added engineering exports are steadily transforming Bangladesh into an emerging South Asian hardware manufacturing hub.',
            ]],

            // ==========================================
            // --- BUSINESS: BANKING (business-banking) ---
            // ==========================================
            ['central-bank-eases-remittance-transfer-protocols', 'business-banking', 'Central Bank eases remittance transfer protocols for wage earners', 'Simplified digital remittance pipelines offer real-time incentives and lower transaction fees for overseas workers.', 'news-tech.png', 4, 110, 9240, true, [
                'Bangladesh Bank has announced revised operational guidelines enabling instant cross-border wallet settlements for non-resident wage earners.',
                'Expatriate workers using verified banking apps will benefit from direct government incentive deposits credited without intermediary delays.',
                'Commercial banks welcomed the reforms, anticipating substantial growth in formal financial inflows over coming quarters.',
            ]],
            ['mobile-financial-services-reach-remote-char-islands', 'business-banking', 'Mobile financial services expand agent banking to remote char islands', 'Solar-powered mobile terminals bring savings, micro-insurance and agricultural credit to underserved river communities.', 'news-tech.png', 3, 590, 3650, false, [
                'Agent banking networks have established dedicated service kiosks across island chars along the Jamuna and Padma rivers.',
                'Local residents can now receive farm subsidies, open savings accounts, and transfer funds without undertaking perilous boat journeys.',
                'Financial literacy workshops conducted in village bazaars are helping women entrepreneurs apply for low-interest micro-enterprise loans.',
            ]],
            ['green-banking-refinance-facility-disburses-record-credit', 'business-banking', 'Central Bank’s green refinancing facility disburses record credit for industrial sustainability', 'Low-interest revolving funds finance effluent treatment plants, rooftop solar arrays and zero-waste factories.', 'news-hero.png', 4, 420, 2870, false, [
                'Bangladesh Bank reported that commercial lenders disbursed record volumes of concessionary financing under the Sustainable Finance Policy.',
                'Participating textile and manufacturing enterprises used the low-cost loans to install closed-loop water recycling and smart thermal recovery boilers.',
                'Bankers highlighted that strong environmental compliance portfolios are increasingly rewarded with lower sovereign risk ratings.',
            ]],

            // ==========================================
            // --- BUSINESS: MARKETS (business-markets) ---
            // ==========================================
            ['jute-diversified-products-gain-surging-demand-in-global-markets', 'business-markets', 'Jute-diversified packaging and home furnishings capture surging global demand', 'Eco-friendly biodegradable shopping bags, geo-jute and artisanal home decor products achieve double-digit market growth.', 'news-rice.png', 4, 190, 4120, false, [
                'Export revenues from diversified golden-fibre products reached multi-year peaks as international bans on single-use plastics expanded.',
                'Artisanal cooperatives and industrial mills in Faridpur and Khulna are fulfilling massive bulk orders for biodegradable packaging across Europe and North America.',
                'Research institutes are supporting growers with improved high-yield jute seeds that require fewer days for microbial water retting.',
            ]],
            ['commodity-exchange-platform-launches-for-agricultural-futures', 'business-markets', 'National agricultural commodity exchange platform begins pilot trading', 'Digital warehouse receipt system guarantees fair floor prices for farmers while curbing speculative market inflation.', 'news-tech.png', 4, 630, 3350, false, [
                'The Securities and Exchange Commission in partnership with the Ministry of Agriculture has launched the nation’s first digital commodity trading platform.',
                'Smallholders storing grain in accredited district silos receive electronic receipts that can be traded transparently on the exchange or pledged for bank credit.',
                'Market economists lauded the institutional reform as a game-changer for farmer price realization and national food supply chain stability.',
            ]],

            // ==========================================
            // --- BUSINESS: INDUSTRY (business-industry) ---
            // ==========================================
            ['startups-secure-regional-seed-investments-in-agritech', 'business-industry', 'Homegrown agritech startups secure regional seed investments', 'Data-driven crop advisory platforms and cold-chain logistics apps attract early-stage venture funding.', 'news-rice.png', 4, 1140, 2150, false, [
                'Two Bangladeshi agriculture-technology startups have raised venture funding to expand farm-to-door distribution channels.',
                'Their platforms utilize smartphone satellite imagery and soil sensor telemetry to help smallholders optimize fertilizer applications.',
                'Investors cited the immense scalability of agricultural digitisation across South Asian delta economies as a primary investment catalyst.',
            ]],
            ['pharmaceutical-active-ingredient-park-in-munshiganj-expands', 'business-industry', 'Active Pharmaceutical Ingredient Park in Munshiganj expands domestic raw material synthesis', 'Local chemical synthesis facilities reduce dependency on imported raw materials for essential life-saving medicines.', 'news-hero.png', 4, 340, 3780, false, [
                'Pharmaceutical manufacturers have operationalized twelve modern synthetic chemical synthesis plants at the dedicated API Industrial Park.',
                'The facility features a common effluent treatment plant and solvent recovery systems complying with the highest international environmental standards.',
                'Industry executives noted that domestic raw material synthesis will protect consumers from global pharmaceutical supply disruptions.',
            ]],
            ['shipbuilding-yards-secure-orders-for-hybrid-cargo-vessels', 'business-industry', 'Bangladeshi shipyards secure European export orders for eco-friendly hybrid vessels', 'Advanced naval engineering facilities in Meghna river corridor build energy-efficient container and multipurpose feeder ships.', 'watch-river.png', 5, 870, 2690, false, [
                'Private shipbuilding yards have signed major vessel construction contracts with maritime operators from Germany, the Netherlands and Norway.',
                'The state-of-the-art hybrid cargo vessels feature battery-assisted propulsion, optimized hull hydrodynamics, and low-emission dual-fuel engines.',
                'Maritime engineers celebrated the achievement, noting that Bangladeshi shipyards possess world-class technical talent and high-precision steel fabrication standards.',
            ]],

            // ==========================================
            // --- SPORTS: CRICKET (sports-cricket) ---
            // ==========================================
            ['bangladesh-clinches-thrilling-t20-series-decider', 'sports-cricket', 'Bangladesh clinches thrilling T20 series decider in final-over finish', 'Disciplined death bowling and resilient middle-order partnerships seal an unforgettable home victory in Mirpur.', 'watch-hero.png', 4, 15, 9780, true, [
                'A packed Sher-e-Bangla National Cricket Stadium erupted in celebration as the national team defended a tense final over against formidable visitors.',
                'The pace attack bowled with pinpoint accuracy in the closing overs, executing yorkers and subtle slower deliveries under intense pressure.',
                'The team captain commended the youngsters for maintaining composure and executing team strategies during high-stakes moments.',
            ]],
            ['premier-league-cricket-features-promising-pace-discoveries', 'sports-cricket', 'Dhaka Premier League unearths exciting young fast bowlers clocking over 140 kph', 'Grassroots talent scouts highlight high-velocity teenage seamers discovered through divisional pace bowling hunt programs.', 'news-hero.png', 4, 180, 4890, false, [
                'The ongoing domestic cricket league has witnessed exhilarating performances from a new crop of raw, dynamic young pace bowlers.',
                'Coaching experts attributed the rapid emergence of genuine fast bowling talent to upgraded high-performance training camps in Sylhet and Bogura.',
                'National selectors confirmed that top performers will receive specialized biomechanics coaching and join the national developmental squad.',
            ]],
            ['bangladesh-under-19-cricket-team-triumphs-in-tri-nation-cup', 'sports-cricket', 'Bangladesh Under-19 cricket team clinches Tri-Nation Youth Cup with unbeaten streak', 'Disciplined fielding, captain’s century and incisive spin bowling secure comprehensive tournament triumph.', 'watch-kids.png', 4, 520, 6120, true, [
                'The junior national cricket squad delivered a commanding all-round performance to lift the international youth championship trophy.',
                'The top-order batsmen displayed immense maturity on challenging turf pitches, stitching match-winning partnerships in crucial moments.',
                'Cricket analysts noted that the junior pipeline remains one of the strongest in world cricket, promising sustained success for future senior teams.',
            ]],

            // ==========================================
            // --- SPORTS: FOOTBALL (sports-football) ---
            // ==========================================
            ['national-women-football-team-qualifies-for-asian-cup-stage', 'sports-football', 'National women’s football team qualifies for Asian Cup knockout stage', 'Dominant attacking teamwork and defensive composure earn historic qualification on the continental stage.', 'watch-kids.png', 4, 140, 8340, true, [
                'The Bangladesh national women’s football squad created history by advancing to the tournament knockout stages with an unbeaten group record.',
                'Spectacular wing play and resolute goalkeeping thwarted opposition attacks throughout a grueling ninety-minute fixture.',
                'Supporters across the country took to social media and city squares to applaud the players’ inspiring tenacity and sporting excellence.',
            ]],
            ['bangladesh-premier-league-football-records-highest-fan-turnout', 'sports-football', 'Premier League Football matches draw capacity crowds across divisional stadiums', 'Thrilling derby fixtures, foreign marquee signings and electric stadium atmospheres re-energize club football fandom.', 'news-hero.png', 4, 390, 4670, false, [
                'Football stadiums in Mymensingh, Cumilla and Gopalganj have witnessed packed stands as club rivalries reached fever pitch.',
                'Fans praised the enhanced stadium lighting, family-friendly security arrangements, and live high-definition television coverage.',
                'Club officials noted that vibrant gate receipts and grassroots supporter clubs are creating sustainable commercial models for domestic football.',
            ]],
            ['grassroots-youth-football-academies-launched-in-eight-divisions', 'sports-football', 'Federation launches residential youth football academies in eight divisions', 'Comprehensive grassroots scouting program provides professional coaching, academic schooling and nutrition for teenage footballers.', 'news-tech.png', 4, 780, 3120, false, [
                'The Football Federation has inaugurated state-of-the-art residential training academies designed to nurture emerging under-15 talents.',
                'International coaching directors will oversee curriculum development, focusing on tactical spatial awareness, ball control and physical conditioning.',
                'Selected academy trainees will represent regional squads in the upcoming National Youth Championship tournament.',
            ]],

            // ==========================================
            // --- SPORTS: LOCAL (sports-local) ---
            // ==========================================
            ['district-kabaddi-championship-draws-huge-crowds-in-bogura', 'sports-local', 'District Kabaddi Championship draws enthusiastic crowds across Bogura', 'Traditional rural teams showcase breathtaking agility, strength and sportsmanship in the annual provincial tournament.', 'watch-music.png', 3, 980, 2670, false, [
                'Thousands of rural sports enthusiasts gathered in Bogura to witness the climax of the divisional traditional Kabaddi championship.',
                'Local clubs fielded balanced squads combining veteran raiders with energetic young university athletes in pulsating matches.',
                'Organizers emphasized that rejuvenating traditional grassroots sports fosters youth fitness and community solidarity across rural districts.',
            ]],
            ['traditional-boat-race-nouka-baich-mesmerizes-thousands-on-surma', 'sports-local', 'Centuries-old Nouka Baich boat race electrifies thousands along the Surma River', 'Over fifty colourfully decorated racing boats compete in rhythmic unison to the thunderous beats of traditional drums.', 'watch-river.png', 4, 450, 5840, true, [
                'The iconic Surma riverbanks in Sylhet were alive with deafening cheers as long, slender racing canoes sliced through the water during the annual Nouka Baich.',
                'Rowers clad in matching bandanas sang traditional sari gaan melodies in perfect sync, demonstrating extraordinary stamina and rowing coordination.',
                'Local cultural trusts presented brass championship shields to the winning village boat clubs amidst joyous riverside festivities.',
            ]],
            ['national-badminton-championship-unearths-teenage-prodigy', 'sports-local', 'National Badminton Championship witnesses thrilling upsets by young shuttlers', 'Fast-paced baseline rallies and deceptive smash techniques dominate the wooden indoor courts in Chattogram.', 'news-hero.png', 3, 890, 2340, false, [
                'A seventeen-year-old rising shuttler from Dinajpur stunned seeded veterans to clinch the national men’s singles badminton title.',
                'Spectators in the packed gymnasium cheered sensational diving saves and blistering jump smashes during the three-set thriller.',
                'The Badminton Federation announced specialized international training sponsorships to prepare the young champion for upcoming regional championships.',
            ]],

            // ==========================================
            // --- SPORTS: INTERNATIONAL (sports-international) ---
            // ==========================================
            ['youth-archery-contingent-claims-gold-at-asiad-qualifiers', 'sports-international', 'Youth archery contingent claims gold medals at Asian qualifiers', 'Sharpshooting recurve and compound teams display extraordinary precision in international ranking matches.', 'news-hero.png', 3, 490, 3120, false, [
                'Bangladesh youth archers delivered standout performances, clinching double gold in both individual and mixed team recurve categories.',
                'Coaches credited specialized sports psychology conditioning and modern optical training simulators for the team’s mental resilience.',
                'The national federation confirmed the medallists will now enter intensive training camps ahead of the World Youth Championships.',
            ]],
            ['bangladesh-shooting-federation-secures-olympic-qualification-berth', 'sports-international', 'National shooting champion secures direct Olympic qualification in 10m air rifle', 'Flawless precision in international championship final earns prestigious direct quota for upcoming Summer Games.', 'news-tech.png', 4, 320, 4150, true, [
                'Bangladeshi marksmen achieved a historic milestone at the Asian Shooting Championship, claiming individual silver and an Olympic quota spot.',
                'The shooter maintained nerves of steel, scoring consistent 10.8s and 10.9s in the tense elimination rounds against top global competitors.',
                'Sports authorities reaffirmed full financial and specialized coaching support during the upcoming Olympic preparation cycle.',
            ]],

            // ==========================================
            // --- ENTERTAINMENT: TELEVISION (entertainment-television) ---
            // ==========================================
            ['new-period-drama-series-depicts-1952-language-movement', 'entertainment-television', 'New television period drama depicts heroic stories of the 1952 Language Movement', 'Meticulous production design and poignant performances bring the historic student struggle to prime-time audiences.', 'watch-hero.png', 4, 530, 4670, false, [
                'A major new television drama series exploring the cultural and intellectual fervor of the 1952 Bengali Language Movement has premiered.',
                'The screenplay dramatizes real-life historical narratives, student underground publications, and the courage of grassroots cultural activists.',
                'Educational institutions and cultural forums have commended the series for its historical fidelity and stirring emotional resonance.',
            ]],
            ['children-science-magazine-show-becomes-weekend-television-hit', 'entertainment-television', 'Interactive science and nature television magazine captures family viewership', 'Fun animated explanations, real-world field experiments and young robot builders inspire the next generation of thinkers.', 'watch-kids.png', 4, 260, 3890, false, [
                'Bangladesh Betar and national television have co-produced a captivating weekend science program that has captured the hearts of young learners.',
                'Each episode tackles fascinating questions about outer space, river ecology, and computer coding through hands-on laboratory demos and animated visual stories.',
                'Teachers report that students regularly recreate the show’s eco-friendly science projects in classroom science fairs across the country.',
            ]],

            // ==========================================
            // --- ENTERTAINMENT: OTT (entertainment-ott) ---
            // ==========================================
            ['bengali-cyber-thriller-series-trends-globally-on-streaming', 'entertainment-ott', 'Bangladeshi original cyber-mystery series trends on global streaming platforms', 'Edge-of-the-seat storytelling, razor-sharp cinematography and authentic Dhaka underworld intrigue win international audiences.', 'news-tech.png', 4, 95, 7840, true, [
                'A groundbreaking new Bengali investigative thriller series exploring dark-web financial heists in Dhaka has topped regional streaming charts.',
                'Critics praised the tight pacing, atmospheric night cinematography, and nuanced performances by veteran stage and screen actors.',
                'The show’s creator announced that production on a second season featuring international filming locations has already commenced.',
            ]],
            ['original-documentary-anthology-explores-historic-river-routes', 'entertainment-ott', 'Streaming documentary anthology chronicles vanishing traditional riverboat crafts', 'High-definition 4K visuals preserve the songs, shipbuilding secrets and heritage of Bengal’s master wooden boat artisans.', 'watch-river.png', 5, 410, 4120, false, [
                'An immersive four-part documentary series streaming on digital platforms takes viewers on an unforgettable journey down historic waterways.',
                'Camera crews spent months documenting the construction of majestic traditional Bajra and Ghasi boats using ancient joinery techniques.',
                'Cultural anthropologists lauded the project as an invaluable visual archive safeguarding intangible maritime cultural heritage.',
            ]],

            // ==========================================
            // --- ENTERTAINMENT: FILMS (entertainment-films) ---
            // ==========================================
            ['indie-film-on-sundarbans-wins-international-festival-acclaim', 'entertainment-films', 'Indie film celebrating Sundarbans forest life wins international acclaim', 'The poetic cinematic drama highlights human-nature harmony, folklore and the resilience of mangrove honey gatherers.', 'watch-river.png', 4, 75, 5840, true, [
                'An independent Bangladeshi feature film set in the deep waterways of the Sundarbans has claimed top honours at a prestigious global film festival.',
                'Critics praised the director’s authentic sound design, immersive cinematography, and non-professional cast drawn directly from local forest communities.',
                'The production team confirmed a nationwide theatrical and community screening tour across universities and divisional auditoriums.',
            ]],
            ['restored-classic-cinematic-masterpieces-screened-at-national-film-archive', 'entertainment-films', 'National Film Archive screens 4K restored celluloid classics of the golden era', 'Digital color correction and audio restoration bring historic 1960s and 70s Bengali masterworks back to the silver screen.', 'news-hero.png', 4, 620, 3210, false, [
                'Film preservationists at the Bangladesh Film Archive have completed meticulous digital restoration for landmark classic films.',
                'Audiences and film students packed the modern archive auditorium to experience legendary cinematography and pristine audio soundtracks.',
                'The archive announced plans to release the restored catalogue online for educational institutions and global diaspora cinephiles.',
            ]],

            // ==========================================
            // --- ENTERTAINMENT: MUSIC (entertainment-music) ---
            // ==========================================
            ['betar-golden-era-musical-archive-digitized-for-streaming', 'entertainment-music', 'Betar’s golden era musical archive digitized for on-demand streaming', 'Master tapes of iconic studio recordings, classical ragas and folk melodies are restored in high-fidelity audio.', 'watch-music.png', 5, 210, 7120, true, [
                'Bangladesh Betar has completed digital preservation and noise-reduction remastering for thousands of historic studio recordings from the 1960s and 70s.',
                'Listeners can now explore rare performances by legendary vocalists, instrumental maestros, and celebrated radio drama troupes.',
                'Curators highlighted that digital indexing ensures priceless national cultural assets remain accessible to generations of music lovers worldwide.',
            ]],
            ['fusion-orchestra-combines-sitar-and-cello-in-enchanting-concert', 'entertainment-music', 'Sitar and Western classical symphony orchestra perform enchanting fusion concert', 'Master instrumentalists weave intricate evening ragas with Western classical string harmonies in packed Dhaka auditorium.', 'watch-music.png', 4, 380, 4890, false, [
                'A spellbinding musical collaboration between traditional Indian classical maestros and a visiting symphony orchestra drew standing ovations.',
                'The ensemble performed experimental compositions blending Raga Yaman with classical string counterpoints, creating a rich acoustic tapestry.',
                'Music critics praised the seamless cultural dialogue and announced that live concert recordings will be released across digital music portals.',
            ]],
            ['nazrul-geeti-modern-acoustic-renditions-draw-millions-of-streams', 'entertainment-music', 'Modern acoustic renditions of Kazi Nazrul Islam’s revolutionary songs go viral', 'Young indie musicians breathe vibrant acoustic life into timeless poetry, inspiring millions of digital music listeners.', 'news-hero.png', 4, 740, 5670, true, [
                'A new studio compilation album featuring acoustic guitar, esraj and live percussion renditions of Rebel Poet Nazrul’s classics has topped streaming playlists.',
                'Listeners praised the pristine acoustic sound engineering and respectful adherence to authentic classical swaralipi structures.',
                'Music educators noted that modern arrangements are successfully reconnecting Gen-Z listeners with Bengal’s revolutionary literary heritage.',
            ]],

            // ==========================================
            // --- ENTERTAINMENT: DRAMA (entertainment-drama) ---
            // ==========================================
            ['dhaka-theatre-festival-showcases-young-playwrights', 'entertainment-drama', 'Dhaka Theatre Festival showcases daring original works by young playwrights', 'Contemporary stage plays tackle urban life, digital connection, and generational identity in packed auditorium runs.', 'watch-kids.png', 3, 1210, 1890, false, [
                'The annual National Theatre Festival opened in Dhaka with a vibrant lineup of original stage plays penned by emerging dramatists.',
                'Experimental stagecraft, live acoustic folk instruments, and minimalist set designs drew standing ovations from enthusiastic theatregoers.',
                'Festival directors announced travel fellowships to support promising production troupes touring their plays across regional district towns.',
            ]],
            ['radio-drama-festival-features-contemporary-social-comedies', 'entertainment-drama', 'National Radio Drama Festival airs hilarious new social comedies and thrillers', 'Award-winning voice actors and sound effect foley masters bring fresh audio scripts to millions of nationwide listeners.', 'news-hero.png', 4, 490, 3120, false, [
                'Bangladesh Betar’s Drama Division has launched its annual radio drama season with ten original plays written by contemporary authors.',
                'The audio dramas employ sophisticated binaural sound recording to place listeners right in the middle of bustling village bazaars and stormy river crossings.',
                'Radio fan clubs organized group listening sessions in campus dormitories and village tea stalls, sharing praise across social media.',
            ]],

            // ==========================================
            // --- ENTERTAINMENT: INTERVIEWS (entertainment-interviews) ---
            // ==========================================
            ['master-foley-artist-shares-fifty-years-of-acoustic-sound-magic', 'entertainment-interviews', 'In conversation with Master Foley Artist: Fifty years of creating acoustic radio magic', 'Veteran studio craftsman reveals how tin sheets, coconut shells and gravel trays bring radio dramas to vivid life.', 'news-tech.png', 5, 310, 4120, false, [
                'In an exclusive retrospective interview, Betar’s longest-serving acoustic sound designer demonstrated his signature live sound effect techniques.',
                'He recounted unforgettable live broadcasts during the 1971 wartime era and shared his methods for mentoring the next generation of audio artisans.',
                'The feature includes high-definition audio clips demonstrating how everyday physical objects produce breathtaking cinematic soundscapes.',
            ]],

            // ==========================================
            // --- JOBS: GOVERNMENT (jobs-government) ---
            // ==========================================
            ['bcs-recruitment-circular-announces-new-technical-cadres', 'jobs-government', 'PSC announces updated recruitment circular with specialized technical cadres', 'Civil service intake expands opportunities in data analytics, environmental science and cybersecurity engineering.', 'news-tech.png', 4, 45, 9460, true, [
                'The Public Service Commission has released its latest recruitment notice featuring newly designated technical cadres for public administration.',
                'Candidates with backgrounds in software engineering, statistics, and ecological management are encouraged to apply for dedicated specialist tracks.',
                'Officials reiterated strict adherence to transparent, computer-assisted preliminary screenings and merit-driven viva evaluations.',
            ]],
            ['primary-teacher-recruitment-results-published-nationwide', 'jobs-government', 'Primary teacher recruitment results published nationwide with merit quotas', 'Thousands of qualified candidates appointed to rural elementary schools to strengthen foundational education.', 'watch-kids.png', 3, 1050, 8150, false, [
                'The Directorate of Primary Education has published final merit lists for the nationwide recruitment of primary school assistant teachers.',
                'The transparent selection process utilized biometric verification and automated score tabulations to ensure complete integrity.',
                'Newly appointed teachers will undergo an intensive orientation program covering child-centered pedagogy and digital classroom tools.',
            ]],
            ['judicial-service-commission-issues-assistant-judge-intake-notice', 'jobs-government', 'Judicial Service Commission announces competitive examination for Assistant Judges', 'Law graduates nationwide prepare for rigorous preliminary, written and viva examinations for subordinate court appointments.', 'news-hero.png', 4, 380, 4180, false, [
                'The Bangladesh Judicial Service Commission has invited online applications for the upcoming batch of Assistant Judge positions.',
                'The recruitment drive aims to expedite judicial disposal rates and strengthen grassroots access to justice across district courts.',
                'Commission guidelines emphasize merit-based evaluations, legal analytical writing, and strict ethical standards.',
            ]],

            // ==========================================
            // --- JOBS: PRIVATE (jobs-private) ---
            // ==========================================
            ['it-freelancing-skill-hubs-launched-in-twenty-districts', 'jobs-private', 'IT freelancing and remote skill training hubs launched in twenty districts', 'Government-backed digital training centers equip university graduates with skills for global remote freelancing markets.', 'news-hero.png', 4, 195, 6780, true, [
                'The ICT Division has inaugurated twenty state-of-the-art vocational training academies offering subsidized courses in web development and UX design.',
                'Enrolled trainees receive high-speed broadband access, international mentor sessions, and hands-on guidance in securing remote global contracts.',
                'Graduates from initial pilot batches have already begun contributing significant export service revenue through formal banking channels.',
            ]],
            ['polytechnic-graduates-gain-fast-track-industrial-apprenticeships', 'jobs-private', 'Polytechnic graduates gain fast-track industrial apprenticeships nationwide', 'Public-private partnership links diploma engineers directly with automotive, electrical and manufacturing plants.', 'news-tech.png', 3, 620, 3420, false, [
                'A new industrial internship agreement guarantees six-month paid apprenticeships for thousands of state polytechnic diploma graduates.',
                'Participating conglomerates provide hands-on factory floor training, robotics maintenance exposure, and pathways to permanent technician roles.',
                'Education authorities noted the initiative bridges longstanding industry-academia gaps and enhances domestic engineering capabilities.',
            ]],
            ['fintech-and-e-commerce-sectors-announce-thousands-of-tech-openings', 'jobs-private', 'Fintech and e-commerce companies announce massive hiring drive for tech talent', 'Surging digital economy creates high-demand openings for cloud architects, mobile developers and product managers.', 'news-tech.png', 4, 480, 5240, false, [
                'Leading Bangladeshi digital banking, mobile wallet and logistics enterprises have unveiled extensive recruitment pipelines for technical specialists.',
                'Recruiters offer competitive compensation packages, flexible hybrid working models, and structured technical mentorship programs.',
                'Industry surveys indicate that domestic software engineering salaries have risen significantly due to strong demand for local talent.',
            ]],

            // ==========================================
            // --- JOBS: CAREER (jobs-career) ---
            // ==========================================
            ['career-counseling-bootcamps-guide-graduates-in-emerging-ai-fields', 'jobs-career', 'Career counseling bootcamps guide graduates towards emerging AI and data roles', 'Industry mentors and university alumni conduct hands-on workshops on portfolio building, technical interviews and certifications.', 'news-hero.png', 4, 290, 3890, false, [
                'A series of nationwide career preparedness bootcamps is helping recent university graduates transition into high-growth tech careers.',
                'Participants receive personalized feedback on technical GitHub portfolios, mock coding assessments, and professional networking strategies.',
                'Organizers stressed the importance of continuous lifelong upskilling to stay competitive in an evolving artificial intelligence landscape.',
            ]],
            ['soft-skills-and-english-communication-workshops-expand-in-colleges', 'jobs-career', 'Workplace communication and professional leadership workshops expand in colleges', 'Interactive training modules prepare rural college graduates for corporate interviews and international remote teams.', 'news-tech.png', 3, 710, 2670, false, [
                'Youth development foundations have partnered with district degree colleges to offer intensive corporate readiness training.',
                'Modules emphasize professional business writing, public speaking, conflict resolution, and collaborative project management.',
                'Participating students reported marked improvements in confidence during campus recruitment drives and internship interviews.',
            ]],

            // ==========================================
            // --- JOBS: RESULTS (jobs-results) ---
            // ==========================================
            ['bankers-selection-committee-publishes-senior-officer-merit-list', 'jobs-results', 'Bankers’ Selection Committee publishes final merit list for state-owned banks', 'Over three thousand qualified candidates appointed as Senior Officers across commercial financial institutions.', 'news-hero.png', 3, 140, 7890, true, [
                'The Bankers’ Selection Committee under Bangladesh Bank has officially released the combined recruitment merit list for state banks.',
                'Successful candidates will undergo comprehensive orientation at the Bangladesh Institute of Bank Management before joining regional branches.',
                'The recruitment process was commended for swift digital examination grading and complete transparency.',
            ]],
            ['nursing-and-midwifery-recruitment-examination-results-declared', 'jobs-results', 'Directorate General of Nursing publishes recruitment results for staff nurses', 'Newly certified nurses deployed to upazila health complexes and specialized tertiary hospitals nationwide.', 'watch-kids.png', 3, 620, 4310, false, [
                'The Ministry of Health has published final recruitment rankings for thousands of registered nurses and certified midwives.',
                'The influx of qualified healthcare professionals directly strengthens maternal care, emergency response and pediatric wards in rural hospitals.',
                'Selected candidates expressed joy and dedication to providing compassionate public healthcare across underserved community clinics.',
            ]],

            // ==========================================
            // --- LIFESTYLE: HEALTH (lifestyle-health) ---
            // ==========================================
            ['community-health-clinics-introduce-telemedicine-counseling', 'lifestyle-health', 'Community health clinics introduce remote telemedicine and mental wellbeing counseling', 'Village healthcare outposts connect rural patients with specialist doctors and psychologists via high-definition video links.', 'news-hero.png', 4, 240, 3890, true, [
                'Rural community clinics across several northern upazilas have begun offering weekly telemedicine consultation sessions with specialist doctors.',
                'Patients receive free diagnostic consultations, prescription guidance, and confidential mental health counseling previously unavailable locally.',
                'Health workers reported overwhelming positive responses from mothers, seniors, and students seeking timely health advice.',
            ]],
            ['nationwide-nutrition-campaign-promotes-indigenous-superfoods', 'lifestyle-health', 'Public health nutrition initiative promotes indigenous greens, small fish and millet', 'Nutritionists advocate traditional dietary diversity to combat micronutrient deficiencies and enhance childhood development.', 'news-rice.png', 4, 380, 3120, false, [
                'Public health agencies have launched an engaging grassroots awareness campaign highlighting the extraordinary health benefits of native foods.',
                'Mola and Dhela small freshwater fish, water spinach, and unpolished red rice are celebrated as affordable, powerhouse sources of calcium and iron.',
                'Mothers’ clubs in rural community centers are sharing creative recipes that integrate indigenous greens into daily family meals.',
            ]],
            ['mental-wellbeing-hotline-provides-round-the-clock-support-for-youth', 'lifestyle-health', 'National toll-free mental health helpline expands round-the-clock counseling', 'Certified clinical psychologists offer confidential emotional support, exam stress guidance and crisis counseling.', 'news-tech.png', 3, 670, 2890, false, [
                'The Directorate General of Health Services has upgraded its dedicated toll-free mental health support helpline with additional telephone lines.',
                'Young callers can access compassionate, non-judgmental guidance for academic anxiety, relationship stress, and depression from licensed counselors.',
                'Mental health advocates commended the service for de-stigmatizing emotional wellbeing and providing accessible help to citizens nationwide.',
            ]],

            // ==========================================
            // --- LIFESTYLE: EDUCATION (lifestyle-education) ---
            // ==========================================
            ['student-robotics-team-heads-to-regional-final', 'lifestyle-education', 'Student robotics team heads to regional innovation final', 'The university team built a low-cost inspection rover using locally available components and open-source tools.', 'news-tech.png', 4, 60, 4560, true, [
                'A student engineering team has qualified for a regional innovation final with a compact rover designed to inspect difficult indoor spaces.',
                'The prototype combines affordable sensors with locally sourced parts, allowing the students to repair and adapt it without specialist equipment.',
                'The team hopes the project will encourage more schools and universities to create practical robotics clubs.',
            ]],
            ['interactive-smart-classrooms-transform-rural-high-schools', 'lifestyle-education', 'Interactive digital smart classrooms installed across thousand rural high schools', 'Multimedia projectors, interactive whiteboards and digitized curricula make STEM learning engaging for village students.', 'watch-kids.png', 4, 430, 3780, false, [
                'The Secondary Education Quality Improvement Program has equipped hundreds of rural secondary schools with modern digital learning labs.',
                'Science and mathematics teachers use interactive simulations and 3D visual animations to explain complex physics and biology concepts.',
                'School headmasters reported significant increases in student attendance, classroom participation, and science exam scores.',
            ]],

            // ==========================================
            // --- LIFESTYLE: TRAVEL (lifestyle-travel) ---
            // ==========================================
            ['eco-tourism-trails-gain-popularity-in-bandarban-hills', 'lifestyle-travel', 'Community-led eco-tourism trails gain popularity in Bandarban hill tracts', 'Sustainable trekking routes support indigenous homestays, conservation guides and traditional organic cuisine.', 'watch-river.png', 4, 780, 2970, false, [
                'Eco-conscious travellers are flocking to newly mapped walking trails managed cooperatively by indigenous communities in the Chittagong Hill Tracts.',
                'Guided treks follow strict zero-plastic principles, offering visitors authentic cultural exchanges and serene mountain vistas.',
                'Homestay operators report that sustainable tourism revenue provides vital income for village schools and reforestation nurseries.',
            ]],
            ['heritage-walking-tours-unveil-architectural-wonders-of-old-dhaka', 'lifestyle-travel', 'Heritage walking trails reveal historic merchant mansions and culinary secrets of Old Dhaka', 'Guided weekend walks take history buffs through narrow lanes, Mughal caravanserais and historic spice markets.', 'news-hero.png', 4, 310, 4890, true, [
                'Architectural conservation groups have inaugurated curated walking trails through the centuries-old alleys of historic Old Dhaka.',
                'Visitors explore beautifully preserved Armenian churches, ornate colonial merchant palaces, and savor authentic Bakarkhani and spiced tea.',
                'Tour organizers highlighted that community engagement encourages local residents and municipal bodies to preserve irreplaceable historic structures.',
            ]],
            ['tanguar-haor-monsoon-houseboat-cruising-attracts-nature-lovers', 'lifestyle-travel', 'Monsoon houseboat expeditions in Tanguar Haor offer serene wetland escape', 'Eco-friendly wooden houseboats glide across crystal-clear waters beneath the majestic Meghalaya mountain backdrop.', 'watch-river.png', 4, 580, 5120, false, [
                'The vast Ramsar wetland sanctuary of Tanguar Haor has become a prime monsoon destination for nature enthusiasts and photographers.',
                'Travellers stay aboard well-appointed traditional wooden boats equipped with solar electricity and strict bio-waste management systems.',
                'Guests enjoy fresh local wetland fish delicacies, stargazing from upper decks, and listening to live acoustic folk singing under moonlight.',
            ]],

            // ==========================================
            // --- LIFESTYLE: FOOD (lifestyle-food) ---
            // ==========================================
            ['revival-of-traditional-pitha-festivals-celebrates-winter-heritage', 'lifestyle-food', 'Winter Pitha festivals celebrate centuries of traditional culinary heritage', 'Artisanal culinary artists prepare steaming Bhapa, crunchy Chitoi, and sweet Patishapta in vibrant open-air street stalls.', 'news-rice.png', 3, 210, 4670, true, [
                'Towns and cities across the country have embraced the arrival of winter with bustling outdoor traditional pitha food fairs.',
                'Master home cooks demonstrate traditional clay stove steaming methods, using freshly ground date palm jaggery, coconut and fragrant rice flour.',
                'Food historians celebrated the revival of vintage pitha varieties, noting that traditional culinary arts unite families across generations.',
            ]],
            ['organic-kitchen-gardening-takes-root-in-urban-rooftops', 'lifestyle-food', 'Urban rooftop farming takes root as city residents grow organic vegetables', 'Rooftop greening initiatives transform concrete rooftops into productive gardens yielding fresh tomatoes, chili and leafy greens.', 'watch-river.png', 4, 730, 2940, false, [
                'A growing urban farming movement in Dhaka and Chattogram has seen thousands of building owners transform empty rooftops into lush gardens.',
                'Using lightweight soil mixtures, drip irrigation and organic compost, residents harvest chemical-free vegetables while cooling building temperatures.',
                'City authorities are encouraging the trend with municipal tax incentives and free organic seedling distribution at community centers.',
            ]],

            // ==========================================
            // --- LIFESTYLE: FASHION (lifestyle-fashion) ---
            // ==========================================
            ['traditional-handloom-weavers-bridge-heritage-and-modern-fashion', 'lifestyle-fashion', 'Traditional Jamdani and Khadi weavers bridge heritage craft and modern fashion', 'Artisanal weaving cooperatives collaborate with young designers to create ethical, breathable contemporary garments.', 'news-rice.png', 4, 1400, 2340, false, [
                'Master handloom weavers in Tangail and Sonargaon are collaborating with urban apparel designers to reimagine historic textile patterns.',
                'Using pure organic cotton and natural plant dyes, the cooperatives produce lightweight fabrics celebrated for durability and elegant textures.',
                'Exhibitions in Dhaka and abroad are creating direct fair-trade sales channels that preserve master weaving lineages.',
            ]],
            ['sustainable-jute-apparel-collection-makes-waves-at-fashion-week', 'lifestyle-fashion', 'Eco-friendly blended jute fabrics and apparel take center stage at National Fashion Week', 'Innovative lightweight jute-cotton blended garments win international buyer acclaim for texture and sustainability.', 'news-hero.png', 4, 490, 3680, false, [
                'Designers at the Dhaka Sustainable Fashion Showcase unveiled a stunning collection of tailored blazers, dresses and accessories made from refined jute fibre.',
                'The breathable, natural textile offers high tensile strength and complete biodegradability, presenting a green alternative to synthetic fabrics.',
                'International fashion houses expressed strong interest in sourcing ethical blended fabrics from Bangladeshi weaving clusters.',
            ]],

            // ==========================================
            // --- VIDEO: NEWS VIDEO (video-news) ---
            // ==========================================
            ['community-radio-expands-agriculture-bulletins', 'video-news', 'Community radio expands daily agriculture bulletins and weather alerts', 'New regional segments will share market prices, weather guidance and advice from agricultural extension officers.', 'news-rice.png', 3, 120, 3840, true, [
                'Regional radio bulletins are expanding to provide farmers with more frequent weather, crop and market information.',
                'The short programmes will be broadcast at times chosen with local listeners and repeated for people working away from home during the day.',
                'Producers said listeners will also be able to submit questions for future episodes.',
            ]],
            ['video-bulletin-metro-rail-terminal-interchange-in-full-motion', 'video-news', 'Video Special: How modern terminal interchanges streamline public transit in Dhaka', 'Aerial camera footage and commuter interviews showcase the seamless integration of metro rail, electric buses and pedestrian plazas.', 'news-hero.png', 4, 280, 5190, false, [
                'A special video news report examines the daily operations of Dhaka’s busiest integrated multimodal transit terminal.',
                'Camera crews follow morning commuters transitioning from elevated metro platforms to synchronized city feeder bus bays in under three minutes.',
                'Urban transport planners explain upcoming interchange expansions planned for western and eastern city corridors.',
            ]],

            // ==========================================
            // --- VIDEO: INTERVIEWS (video-interviews) ---
            // ==========================================
            ['video-conversation-with-pioneering-delta-hydrologist-on-river-resilience', 'video-interviews', 'Video Dialogue: Renowned delta hydrologist on living in harmony with seasonal rivers', 'In-depth interview explores adaptive dredging, nature-based riverbank protection and South Asian water diplomacy.', 'watch-river.png', 5, 340, 4120, true, [
                'In an engaging on-camera conversation beside the Padma River, a leading water resource scientist outlines practical climate adaptation models.',
                'He emphasizes shifting from rigid concrete barriers toward flexible sediment management and localized wetland retention basins.',
                'The video includes detailed computer simulations showing how tidal flows can be harnessed to rebuild natural coastal landmass.',
            ]],

            // ==========================================
            // --- VIDEO: EXPLAINERS (video-explainers) ---
            // ==========================================
            ['explainer-how-the-bangabandhu-tunnel-changes-southern-logistics', 'video-explainers', 'Explainer: How the Bangabandhu Tunnel transforms southern trade logistics', 'Visual analysis examines how South Asia’s first underwater expressway connects economic zones, ports and highway networks.', 'news-hero.png', 5, 310, 7620, true, [
                'An in-depth video feature breaks down the engineering marvel and economic implications of the Karnaphuli underwater expressway tunnel.',
                'Computer graphics illustrate how freight transport bypasses congested city centers to link industrial parks with Chattogram deep-sea terminals.',
                'Economists and transport engineers discuss projected long-term boosts to regional industrial productivity and regional tourism.',
            ]],
            ['explainer-video-how-biometric-smart-nid-secures-public-services', 'video-explainers', 'Visual Explainer: How encrypted Smart NID cards power seamless digital citizen services', 'Animated breakdown illustrates how cryptographic chip authentication enables instant bank account opening and land record verification.', 'news-tech.png', 4, 620, 4890, false, [
                'This animated educational video demonstrates the underlying security architecture of Bangladesh’s national digital identity infrastructure.',
                'Viewers learn how 256-bit hardware encryption protects personal biometric records during daily administrative interactions.',
                'The explainer outlines future integrations with electronic healthcare records, tax portals, and automated passport e-gates.',
            ]],

            // ==========================================
            // --- VIDEO: DOCUMENTARIES (video-documentaries) ---
            // ==========================================
            ['voices-from-the-delta-living-with-the-tides', 'video-documentaries', 'Voices from the Delta: Video documentary on living with seasonal tides', 'Cinematic video report captures the ingenuity, folklore and everyday resilience of river communities on floating homesteads.', 'watch-river.png', 4, 840, 4190, false, [
                'A poignant short documentary chronicles daily life aboard traditional houseboats navigating shifting channels in the lower Meghna delta.',
                'Through intimate camera angles and first-person narratives, boat captains and schoolteachers share their deep relationship with the rivers.',
                'The video has sparked widespread discussions online regarding climate adaptation and the cultural heritage of riparian lifestyles.',
            ]],
            ['field-report-inside-the-national-seed-preservation-vault', 'video-documentaries', 'Field Report: Inside the National Seed Preservation Vault in Gazipur', 'Camera crew visits state-of-the-art cryogenic seed banks securing thousands of indigenous crop varieties for the future.', 'news-tech.png', 4, 1560, 2680, false, [
                'A rare behind-the-scenes video report takes viewers inside the climate-controlled vaults of the Bangladesh Agricultural Research Institute.',
                'Agronomists demonstrate how heirloom rice varieties, wild legumes, and drought-hardy grains are preserved in liquid nitrogen at sub-zero temperatures.',
                'The facility serves as an indispensable genetic safety net safeguarding national food security against unforeseen ecological shocks.',
            ]],

            // ==========================================
            // --- ECONOMY (economy) ---
            // ==========================================
            ['remittance-inflows-reach-six-month-high-ahead-of-festivals', 'economy', 'Remittance inflows reach six-month peak as formal channel transfers climb', 'Expatriate workers send record funds through digital banking pipelines, strengthening national foreign exchange reserves.', 'news-hero.png', 4, 85, 6840, true, [
                'Monthly remittance receipts crossed multi-year benchmarks as overseas wage earners utilized enhanced banking incentives and instant app transfers.',
                'Financial analysts noted the healthy foreign currency reserves provide vital macroeconomic stability for essential commodity imports.',
                'Banks have broadened foreign exchange service desks to provide instantaneous family disbursements across rural upazilas.',
            ]],
            ['inflation-moderates-as-supply-chain-logistics-stabilize', 'economy', 'Inflation moderates as domestic agricultural supply chain logistics stabilize', 'Coordinated transport corridors and bumper seasonal harvests ease retail consumer price pressures across major metropolitan markets.', 'news-rice.png', 4, 430, 4290, false, [
                'Official statistical bulletins indicate a welcome downward trend in core food inflation indicators over the past quarter.',
                'Market surveillance teams and direct farmer-to-consumer open markets helped curtail unnecessary intermediary markups.',
                'Policy economists advocate sustained investments in decentralized rural cold storage to smooth seasonal food price fluctuations permanently.',
            ]],
            ['renewable-energy-investments-surge-across-industrial-zones', 'economy', 'Renewable energy investments surge across special economic zones', 'Rooftop solar installations and industrial waste-to-energy projects reduce factory power overheads and carbon footprints.', 'news-tech.png', 5, 1120, 3110, false, [
                'Dozens of export manufacturing facilities have commissioned rooftop solar photovoltaic arrays totaling hundreds of megawatts of clean energy capacity.',
                'Commercial banks provided green refinancing facilities with attractive concessionary interest rates to accelerate industrial solar adoption.',
                'Industrial park authorities project that captive clean power will strengthen export competitiveness under tightening global supply-chain sustainability standards.',
            ]],

            // ==========================================
            // --- CLIMATE (climate) ---
            // ==========================================
            ['mangrove-afforestation-protects-hundreds-of-coastal-villages', 'climate', 'Community mangrove afforestation shields hundreds of vulnerable coastal villages', 'Locally managed green belts reduce storm surge velocity and restore biodiverse fish breeding grounds.', 'news-coast.png', 5, 150, 5320, true, [
                'Coastal forest divisions alongside community volunteers have planted dense mangrove saplings along thousands of hectares of mudflats.',
                'Hydrological studies confirm the mature coastal green belt significantly dissipates wave energy during severe tropical depressions.',
                'Villagers participating in the forestry co-management committees also receive alternative livelihood training in sustainable crab farming and apiculture.',
            ]],
            ['early-warning-cyclone-networks-cut-response-times-to-minutes', 'climate', 'Smart early-warning cyclone networks cut emergency response times to minutes', 'Automated siren towers, radio broadcasts and instant SMS alerts ensure timely evacuation across offshore islands.', 'watch-river.png', 4, 690, 3780, false, [
                'Disaster management authorities have completed upgrades to the automated coastal multi-hazard warning infrastructure.',
                'The system synthesizes meteorological satellite telemetry with ground radar to trigger multilingual siren alerts and village megaphone announcements.',
                'Recent simulation drills confirmed that vulnerable households can be safely accommodated in multi-purpose cyclone shelters well before landfall.',
            ]],
            ['saline-tolerant-crop-varieties-expand-in-southern-polders', 'climate', 'Saline-tolerant crop varieties expand harvest yields in southern polders', 'Agronomists introduce resilient mustard, pulse and wheat strains allowing farmers to cultivate winter crops on saline soil.', 'news-rice.png', 4, 1450, 2840, false, [
                'Farmers in coastal delta districts are harvesting bumper yields of saline-tolerant winter crops developed by national research institutes.',
                'Lands that previously lay barren after monsoon rice cultivation are now productive year-round, boosting household incomes and soil fertility.',
                'Agricultural extension teams are distributing certified seed packs and bio-fertilizer kits to expand adoption across neighbouring coastal upazilas.',
            ]],
            ['floating-agriculture-baira-farming-expands-in-waterlogged-wetlands', 'climate', 'Traditional floating bed Baira farming adapted for climate-resilient agriculture', 'Wetland communities in southern districts build organic water-hyacinth rafts producing bumper harvests of vegetables.', 'watch-river.png', 4, 380, 3670, false, [
                'The ancient indigenous technique of hydroponic floating agriculture is expanding rapidly across flood-prone wetlands in Pirojpur and Gopalganj.',
                'Farmers construct layered floating rafts from decomposed water hyacinths, cultivating nutrient-rich gourds, spinach, and seedling nurseries.',
                'International agricultural agencies have recognized the zero-chemical Baira system as a globally important agricultural heritage system.',
            ]],

            // ==========================================
            // --- CULTURE (culture) ---
            // ==========================================
            ['pahela-baishakh-preparations-begin-with-traditional-mangol-shobhajatra', 'culture', 'Pahela Baishakh preparations begin with vibrant traditional Mangol Shobhajatra motifs', 'Fine arts students and folk artisans craft colourful masks, peace motifs and giant celebratory effigies.', 'watch-music.png', 4, 175, 7390, true, [
                'Campuses and cultural academies across the country have begun lively preparations for the upcoming Bengali New Year celebrations.',
                'Faculty and student artists at Dhaka University’s Faculty of Fine Arts are creating large-scale sculptural motifs symbolizing harmony, unity and hope.',
                'Cultural enthusiasts and families look forward to joining the world-renowned UNESCO-recognized Mangol Shobhajatra procession at dawn.',
            ]],
            ['baul-music-archive-unveils-rare-recordings-from-kushtia', 'culture', 'Baul music archive unveils rare historical acoustic recordings from Kushtia', 'Scholars and musicologists digitize oral philosophical songs and Lalon Shah verses for global cultural research.', 'watch-hero.png', 5, 760, 3940, false, [
                'A comprehensive digital repository dedicated to Baul philosophical poetry and acoustic folk music has opened to the public.',
                'The collection includes unreleased reel-to-reel field recordings of veteran ektara and dotara players performing at rural riverside shrines.',
                'International musicologists highlighted the universal spiritual depth and ecological philosophy embedded in Bengal’s mystic musical traditions.',
            ]],
            ['national-book-fair-records-record-footfall-and-youth-authors', 'culture', 'National Book Fair records unprecedented footfall and surge in young authors', 'Thousands of literature lovers crowd the historic grounds as new poetry, fiction and science books hit the stalls.', 'watch-kids.png', 4, 1620, 5120, false, [
                'The Amar Ekushey Boi Mela witnessed bustling crowds of avid readers, students, and writers browsing hundreds of brightly lit publishing pavilions.',
                'Publishers reported remarkable enthusiasm for contemporary fiction, investigative non-fiction, and children’s science comic series.',
                'Authors and critics engaged in animated open-air literary discussions celebrating the rich heritage of Bengali language and literature.',
            ]],
            ['folk-craft-fair-in-sonargaon-showcases-centuries-old-wooden-art', 'culture', 'Sonargaon Folk Arts and Crafts Fair celebrates master woodcarvers and weavers', 'Artisans from across sixty-four districts demonstrate heritage Nakshi Kantha stitching, pottery and brass metalwork.', 'news-rice.png', 4, 430, 4210, false, [
                'The historic grounds of the National Folk Art and Craft Foundation in Sonargaon opened to thousands of visitors for the annual heritage fair.',
                'Master craftsmen demonstrated the intricate hand-chiseling of traditional wooden dolls and the delicate embroidery of storied Nakshi Kantha quilts.',
                'Visitors enjoyed traditional puppet theatre, rural baul performances, and authentic hot winter delicacies throughout the festive grounds.',
            ]],

            // ==========================================
            // --- SCIENCE (science) ---
            // ==========================================
            ['biochemists-develop-rapid-water-purity-testing-kits', 'science', 'Biochemists develop low-cost rapid water purity testing strips for rural communities', 'The portable paper-based sensor detects arsenic, heavy metals and bacterial contamination within minutes.', 'news-tech.png', 4, 210, 4610, true, [
                'A team of university biochemists has invented an affordable, easy-to-use water testing strip tailored for field deployment.',
                'The sensor changes colour in the presence of hazardous contaminants, allowing tube-well owners to instantly determine drinking water safety without laboratory equipment.',
                'Public health agencies plan to distribute the testing kits to village sanitation volunteers across flood-prone and coastal districts.',
            ]],
            ['space-research-station-tracks-weather-patterns-with-high-accuracy', 'science', 'Space research and remote sensing station tracks weather patterns with high accuracy', 'Indigenous satellite data processing algorithms enhance rainfall forecasts and flood warning lead times.', 'news-hero.png', 5, 890, 3450, false, [
                'The Space Research and Remote Sensing Organization has unveiled an upgraded satellite telemetry data processing pipeline.',
                'By integrating geostationary cloud tracking with river basin elevation maps, the station generates hyper-localized monsoon rainfall predictions.',
                'Hydrologists and disaster planners commended the improved five-day advance warnings for mitigating urban flash floods and crop damage.',
            ]],
            ['ai-powered-crop-disease-detection-app-rolled-out-for-farmers', 'science', 'AI-powered crop disease detection smartphone app rolled out for field agronomists', 'Machine learning visual diagnostics identify plant pests and nutrient deficiencies from a single leaf photo.', 'news-rice.png', 4, 1680, 4190, false, [
                'An artificial intelligence mobile application trained on hundreds of thousands of local crop pathology images has been released to extension officers.',
                'Farmers can photograph affected paddy, jute, or vegetable leaves to receive instant, localized treatment recommendations in Bengali.',
                'Field trials demonstrated significant reductions in unnecessary pesticide usage and faster recovery for affected harvest plots.',
            ]],

            // ==========================================
            // --- ENVIRONMENT (environment) ---
            // ==========================================
            ['freshwater-dolphin-sanctuaries-see-encouraging-population-rise', 'environment', 'Freshwater dolphin sanctuaries in Padma and Jamuna rivers report encouraging population rise', 'Community-patrolled river reserves, ban on destructive gillnets and cleaner river currents support species recovery.', 'watch-river.png', 4, 260, 4870, true, [
                'Wildlife conservationists have recorded a steady increase in sightings of the endangered Ganges river dolphin across protected river sanctuaries.',
                'Local fishing communities collaborated with wildlife rangers to safeguard deep river pools and rescue dolphins accidentally caught in nets.',
                'Biologists highlighted that a healthy dolphin population reflects improving aquatic biodiversity and river ecosystem vitality.',
            ]],
            ['plastic-waste-reduction-initiative-cleans-major-urban-canals', 'environment', 'Urban canal cleanup and circular recycling initiative clears tons of plastic waste', 'Youth volunteer brigades and municipal workers restore vital storm-water flow channels across metropolitan canals.', 'news-coast.png', 4, 940, 3180, false, [
                'A citywide environmental drive has successfully removed accumulated plastic debris and silt from historic municipal drainage canals.',
                'Collected plastic waste is sorted and delivered to circular recycling plants that convert discarded polymers into durable industrial paving tiles.',
                'City authorities installed floating barrier screens and trash-capture booms to prevent further solid waste accumulation in urban waterways.',
            ]],
            ['community-forest-guard-groups-awarded-for-conservation-efforts', 'environment', 'Community forest guard groups honored for safeguarding tropical evergreen reserves', 'Indigenous village patrols successfully protect biodiversity, old-growth timber and wildlife corridors from illegal logging.', 'news-rice.png', 4, 1740, 2430, false, [
                'Forest department officials presented national conservation honors to community co-management committees in the Lawachara and Satchari forests.',
                'Village patrol teams maintain round-the-clock foot vigils to prevent illegal tree felling and protect habitats of rare primates and birds.',
                'Revenue from eco-tourism guide fees and community nurseries is reinvested into village development projects and school scholarships.',
            ]],
            ['migratory-bird-sanctuaries-in-jahangirnagar-and-haors-report-record-flocks', 'environment', 'Wetland sanctuaries report record arrival of winter migratory guest birds', 'Pristine water quality and strict community anti-poaching vigil attract rare whistling ducks, pochards and sandpipers.', 'news-coast.png', 4, 490, 4320, false, [
                'Ornithologists conducting annual waterbird censuses in Jahangirnagar lakes and Hakaluki Haor have documented over eighty migratory bird species.',
                'Strict local protective measures and expanded aquatic vegetation provide safe nesting and feeding sanctuaries for long-distance migratory flocks.',
                'Wildlife researchers emphasized that thriving bird populations indicate healthy wetland bio-productivity and abundant fish stock.',
            ]],

            // ==========================================
            // --- MEDIA (media) ---
            // ==========================================
            ['bangladesh-betar-celebrates-milestone-with-next-gen-audio-portal', 'media', 'Bangladesh Betar celebrates major broadcast milestone with next-generation digital audio portal', 'Modern streaming apps, live radio channels, podcast series and rich news portal connect millions of global listeners.', 'watch-hero.png', 4, 95, 8920, true, [
                'Bangladesh Betar has unveiled its state-of-the-art public digital platform, bringing national audio heritage and round-the-clock live radio to modern devices.',
                'The platform offers crystal-clear digital live broadcasts, an extensive music and drama archive, and a comprehensive public interest newsroom.',
                'Listeners from across the country and the global diaspora praised the intuitive interface, bilingual accessibility, and rich cultural catalogue.',
            ]],
            ['fact-checking-network-partners-with-rural-community-radio', 'media', 'National fact-checking network partners with rural community radio stations', 'Collaborative broadcast segments debunk online misinformation, health rumors and financial scams for grassroots listeners.', 'news-tech.png', 4, 560, 3670, false, [
                'Journalism organizations and community radio broadcasters have launched a joint initiative to combat digital disinformation in rural communities.',
                'Weekly radio magazines verify viral social media claims, clarify official government announcements, and offer digital literacy tips.',
                'Community leaders reported that the trusted audio broadcasts help protect vulnerable citizens from falling prey to fraudulent online schemes.',
            ]],
            ['journalism-fellowships-awarded-for-investigative-climate-reporting', 'media', 'Prestigious journalism fellowships awarded for investigative climate reporting', 'Reporters from print, television and digital newsrooms receive grants to investigate groundwater, coastal erosion and green transition.', 'news-coast.png', 4, 1380, 2890, false, [
                'The National Press Institute has awarded competitive investigative reporting fellowships to twelve outstanding young journalists.',
                'Fellows will undertake multi-month field investigations into groundwater salinity in the southwest delta and renewable energy transitions in the north.',
                'Editorial mentors from leading media organizations will provide technical guidance in data journalism, multimedia storytelling and ethical reporting.',
            ]],
            ['national-broadcasting-academy-launches-podcast-production-diploma', 'media', 'National Broadcasting Academy launches professional audio podcasting and storytelling diploma', 'Curriculum equips aspiring journalists and audio creators with digital sound engineering, scriptwriting and studio mastering skills.', 'news-tech.png', 4, 380, 3190, false, [
                'The National Institute of Mass Communication has introduced a specialized diploma program in digital podcast production and audio documentary journalism.',
                'Trainees gain hands-on mastery in multitrack acoustic editing, studio microphone acoustics, field recording and audio content marketing.',
                'Broadcasting veterans praised the initiative for preparing next-generation storytellers to enrich the nation’s rapidly expanding audio streaming landscape.',
            ]],
        ];

        $newsBn = [
            // Bangladesh: National
            'rail-link-connects-river-regions' => [
                'নতুন রেলপথে নদীবেষ্টিত অঞ্চলের সঙ্গে রাজধানীর যোগাযোগ সহজ',
                'সম্প্রসারিত রুটটি যাত্রার সময় কমাবে, আঞ্চলিক বাণিজ্য বাড়াবে এবং আরও যাত্রীকে নির্ভরযোগ্য গণপরিবহনের আওতায় আনবে।',
                [
                    'দেশের ব্যস্ততম নদী করিডরের একটি দিয়ে নতুন সম্প্রসারিত রেল যোগাযোগে যাত্রী পরিবহন শুরু হয়েছে। এতে আঞ্চলিক শহরগুলোর সঙ্গে ঢাকার দ্রুত যোগাযোগ তৈরি হয়েছে।',
                    'পরিবহন পরিকল্পনাবিদরা বলছেন, সড়কের চাপ কমানোর পাশাপাশি শিক্ষা, স্বাস্থ্যসেবা ও বাজারে যাতায়াত সহজ করতেই এই সেবা চালু হয়েছে।',
                    'স্থানীয় ব্যবসায়ীরা আশা করছেন, নির্ভরযোগ্য যাত্রাসময় জেলার মধ্যে কৃষিপণ্য ও ক্ষুদ্র শিল্পপণ্য পরিবহন সহজ করবে।',
                ],
            ],
            'coastal-volunteers-complete-shelter-drill' => [
                'উপকূলীয় স্বেচ্ছাসেবকদের আশ্রয়কেন্দ্র মহড়া সম্পন্ন',
                'দুর্যোগ মৌসুমের আগে প্রাথমিক চিকিৎসা, সরিয়ে নেওয়ার পথ ও যোগাযোগব্যবস্থা পরীক্ষা করেছে স্থানীয় দলগুলো।',
                [
                    'উপকূলীয় স্বেচ্ছাসেবকেরা ঘূর্ণিঝড় আশ্রয়কেন্দ্রে পৌঁছানো ও পরিবারের সঙ্গে যোগাযোগের প্রস্তুতি নিয়ে সমন্বিত মহড়া শেষ করেছেন।',
                    'মহড়ায় বয়স্ক, শিশু ও প্রতিবন্ধী মানুষকে সরিয়ে নেওয়ার অনুশীলন এবং জরুরি সরঞ্জাম পরীক্ষা করা হয়।',
                    'ভারী বৃষ্টিতে যেসব প্রত্যন্ত এলাকায় চলাচল কঠিন হয়, সেখানেও এই মহড়া আয়োজন করা হবে।',
                ],
            ],
            'national-universal-pension-enrollment-surpasses-target' => [
                'সর্বজনীন পেনশন স্কিমে ২০ লাখ নাগরিকের নিবন্ধনের মাইলফলক স্পর্শ',
                'প্রবাসী, বেসরকারি চাকরিজীবী, স্বকর্মসংস্থান ও অনানুষ্ঠানিক খাতের বিপুল সাড়া; ঘরে বসেই ডিজিটাল পদ্ধতিতে কিস্তি পরিশোধের সুবিধা।',
                [
                    'জাতীয় পেনশন কর্তৃপক্ষের সার্বিক ব্যবস্থাপনায় সর্বজনীন পেনশনের চারটি স্কিমেই নাগরিকদের নিবন্ধনের সংখ্যা দ্রুত বাড়ছে।',
                    'সহজ মোবাইল অ্যাপ ও অনলাইন ব্যাংকিংয়ের মাধ্যমে প্রবাস ও দেশের প্রত্যন্ত অঞ্চল থেকে নির্বিঘ্নে মাসিক চাঁদা জমা দিচ্ছেন নাগরিকেরা।',
                    'অর্থ মন্ত্রণালয়ের কর্মকর্তারা জানিয়েছেন, মেয়াদপূর্তিতে সম্পূর্ণ ডিজিটাল ও স্বয়ংক্রিয়ভাবে ব্যাংক অ্যাকাউন্টে পেনশনের অর্থ সরাসরি পৌঁছে যাবে।',
                ],
            ],
            'padma-river-bank-embankment-fortification-completed' => [
                'বর্ষা শুরুর আগেই পদ্মার ভাঙনপ্রবণ তীর রক্ষা বাঁধের কাজ সম্পন্ন',
                'জিও-টেক্সটাইল ব্যাগ ও আধুনিক ব্লক বসিয়ে নদীর তীর সুরক্ষা করায় রক্ষা পেল হাজারো বসতভিটা ও ফসলি জমি।',
                [
                    'পানি উন্নয়ন বোর্ডের তত্ত্বাবধানে পদ্মা নদীর তীব্র ভাঙনপ্রবণ এলাকায় স্থায়ী বাঁধ শক্তিশালীকরণ প্রকল্প সফলভাবে শেষ হয়েছে।',
                    'আধুনিক জিও-ব্যাগ ও পাথরের ডাম্পিং নদীর প্রবল স্রোতের আঘাত প্রতিহত করে নদীপারের মাটির স্থায়িত্ব নিশ্চিত করছে।',
                    'স্থানীয় কৃষকেরা স্বস্তি প্রকাশ করে জানিয়েছেন, নদী ভাঙনের স্থায়ী সমাধান হওয়ায় তারা নিশ্চিন্তে কৃষিকাজ চালিয়ে যেতে পারছেন।',
                ],
            ],

            // Bangladesh: Dhaka
            'dhaka-metro-expands-evening-schedule' => [
                'যাত্রীদের সুবিধার্থে ঢাকা মেট্রোর সান্ধ্যকালীন সময়সূচি সম্প্রসারণ',
                'অতিরিক্ত ট্রেন চলাচল এবং প্রান্তিক স্টেশনের বর্ধিত সময় ব্যস্ত সময়ে নগরবাসীর স্বস্তি নিশ্চিত করেছে।',
                [
                    'বাণিজ্যিক এলাকা থেকে ফেরা যাত্রীদের ক্রমবর্ধমান চাপ সামলাতে ঢাকা ম্যাস ট্রানজিট কর্তৃপক্ষ সান্ধ্যকালীন ট্রেনের সংখ্যা বাড়িয়েছে।',
                    'যাত্রীরা জানান, এর ফলে স্টেশনে অপেক্ষার সময় উল্লেখযোগ্যভাবে কমেছে এবং গন্তব্যে পৌঁছানো অনেক সহজ হয়েছে।',
                    'মেট্রো স্টেশনের সঙ্গে মিল রেখে সংযোগকারী বাস রুটের সমন্বয় করা হচ্ছে যাতে শেষ প্রান্ত পর্যন্ত যাতায়াত নির্বিঘ্ন থাকে।',
                ],
            ],
            'dhaka-circular-waterway-launches-modern-eco-catamarans' => [
                'ঢাকার বৃত্তাকার নৌপথে যুক্ত হলো পরিবেশবান্ধব আধুনিক ওয়াটার বাস ও ক্যাটামারান',
                'সদরঘাট, গাবতলী ও আশুলিয়ার মধ্যে আরামদায়ক নৌ-চলাচল নগরবাসীর যানজটের ভোগান্তি লাঘবে নতুন সম্ভাবনা সৃষ্টি করেছে।',
                [
                    'বিআইডব্লিউটিএ ঢাকার বুড়িগঙ্গা ও তুরাগ নদে আধুনিক প্রযুক্তির দ্রুতগতির ওয়াটার বাস সার্ভিস চালু করেছে।',
                    'সড়কের যানজট এড়িয়ে শীতল ও মনোরম পরিবেশে স্বল্প সময়ে এক প্রান্ত থেকে অন্য প্রান্তে যাতায়াত করছেন যাত্রীরা।',
                    'নগর পরিকল্পনাবিদরা বলছেন, নৌপথ নিয়মিত সচল রাখা গেলে রাজধানীর সার্বিক ট্রাফিক ব্যবস্থাপনায় বড় স্বস্তি আসবে।',
                ],
            ],
            'dhaka-smart-traffic-signal-corridor-eases-commuting' => [
                'রাজধানীর প্রধান করিডরে কৃত্রিম বুদ্ধিমত্তাসম্পন্ন স্বয়ংক্রিয় ট্রাফিক সিগন্যাল চালু',
                'যানবাহনের চাপ স্বয়ংক্রিয়ভাবে শনাক্ত করে সবুজ বাতির সময়সীমা নিয়ন্ত্রণ করায় মোড়গুলোতে গাড়ির দীর্ঘ জট কমেছে।',
                [
                    'ঢাকা উত্তর ও দক্ষিণ সিটি করপোরেশনের যৌথ উদ্যোগে রাজধানীর প্রধান সড়কগুলোতে আধুনিক এআই ট্রাফিক সিগন্যাল চালু হয়েছে।',
                    'নতুন এই ব্যবস্থায় স্বয়ংক্রিয় সেন্সর দিয়ে অ্যাম্বুলেন্স ও গণপরিবহনকে অগ্রাধিকার দিয়ে সিগন্যাল পারাপার করানো হচ্ছে।',
                    'ট্রাফিক বিভাগের প্রাথমিক পর্যালোচনায় দেখা গেছে, ব্যস্ততম মোড়গুলোতে গাড়ির অপেক্ষার সময় গড়ে পঁচিশ শতাংশ হ্রাস পেয়েছে।',
                ],
            ],

            // Bangladesh: Chattogram
            'chattogram-port-sets-record-container-throughput' => [
                'ডিজিটাল রূপান্তরে চট্টগ্রাম বন্দরে কনটেইনার হ্যান্ডলিংয়ে নতুন রেকর্ড',
                'স্বয়ংক্রিয় গেট পাস ও স্মার্ট ইয়ার্ড ট্র্যাকিংয়ে জাহাজের অবস্থানকাল বিশ শতাংশ কমেছে।',
                [
                    'স্বয়ংক্রিয় টার্মিনাল ব্যবস্থাপনা সফটওয়্যার চালুর পর চট্টগ্রাম বন্দর কর্তৃপক্ষ পণ্য পরিবহনে ঐতিহাসিক রেকর্ড গড়েছে।',
                    'আমদানিকারক ও শিপিং কোম্পানিগুলো নিরবচ্ছিন্ন কাস্টমস ছাড়করণ ও সার্বক্ষণিক সেবার প্রশংসা করেছে।',
                    'বন্দর কর্তৃপক্ষ জানিয়েছে, আগামী প্রজন্মের বড় কনটেইনার জাহাজ ভেড়ানোর জন্য গভীর ড্রাফটের বার্থ নির্মাণ কার্যক্রম চলমান রয়েছে।',
                ],
            ],
            'karnaphuli-industrial-economic-zone-attracts-foreign-tech' => [
                'কর্ণফুলী অর্থনৈতিক অঞ্চলে বিদেশি বিনিয়োগে গড়ে উঠছে হাইটেক শিল্পকারখানা',
                'টানেল সংযোগের সুবাদে আনোয়ারায় ইলেকট্রনিক্স ও সোলার প্যানেল সংযোজন কারখানায় তৈরি হচ্ছে হাজারো কর্মসংস্থান।',
                [
                    'বাংলাদেশ অর্থনৈতিক অঞ্চল কর্তৃপক্ষ (বেজা) কর্ণফুলী বিশেষ অর্থনৈতিক অঞ্চলে একাধিক বহুজাতিক কোম্পানির কারখানা অনুমোদন দিয়েছে।',
                    'বঙ্গবন্ধু টানেলের কারণে চট্টগ্রাম বন্দর থেকে সরাসরি কাঁচামাল দ্রুত কারখানায় পরিবহন করা সম্ভব হচ্ছে।',
                    'শিল্পোদ্যোক্তারা জানিয়েছেন, এই কারখানায় আধুনিক প্রযুক্তি ব্যবহারের মাধ্যমে স্থানীয় প্রকৌশলীদের দক্ষ হিসেবে গড়ে তোলা হচ্ছে।',
                ],
            ],
            'coxs-bazar-railway-expands-scenic-express-services' => [
                'চট্টগ্রাম-কক্সবাজার পর্যটন এক্সপ্রেসে যুক্ত হলো নয়নাভিরাম ভিস্তাডোম কোচ',
                'পাহাড় ও বনভূমির অপরূপ সৌন্দর্য উপভোগ করতে করতে দ্রুত ও স্বাচ্ছন্দ্যে সমুদ্রসৈকতে পৌঁছাচ্ছেন পর্যটকেরা।',
                [
                    'বাংলাদেশ রেলওয়ে চট্টগ্রাম-কক্সবাজার রুটে দৃষ্টিনন্দন কাচের বড় জানালাযুক্ত আধুনিক ভিস্তাডোম বগি চালু করেছে।',
                    'চুনতি বন্যপ্রাণী অভয়ারণ্য ও পাহাড়ি অঞ্চলের মধ্য দিয়ে রেলযাত্রার অনন্য অভিজ্ঞতা পর্যটকদের দারুণভাবে আকর্ষণ করছে।',
                    'হোটেল-মোটেল মালিকেরা জানিয়েছেন, নতুন রেলযোগাযোগ চালুর পর সারা বছরই পর্যটকদের সমাগম উল্লেখযোগ্য হারে বেড়েছে।',
                ],
            ],

            // Bangladesh: Sylhet
            'sylhet-tea-estates-adopt-solar-irrigation' => [
                'সিলেটের চা বাগানে সৌরবিদ্যুৎচালিত সেচ প্রযুক্তির ব্যবহার',
                'পাহাড়ি পরিবেশ রক্ষা করে শুষ্ক মৌসুমেও উন্নতমানের চা উৎপাদন বজায় রাখছে নতুন প্রযুক্তি।',
                [
                    'সিলেট অঞ্চলের বেশ কয়েকটি ঐতিহ্যবাহী চা বাগান তাদের পাহাড়ি ঢালে সৌরচালিত মাইক্রো-স্প্রিংকলার সেচ নেটওয়ার্ক স্থাপন করেছে।',
                    'বাগান ব্যবস্থাপকেরা জানিয়েছেন, নিয়মিত আর্দ্রতা বজায় থাকায় সাম্প্রতিক খরায় পাতার ক্ষতি রোধ করা সম্ভব হয়েছে।',
                    'এই উদ্যোগ ডিজেল পাম্পের ওপর নির্ভরতা কমিয়েছে, ফলে কার্বন নিঃসরণ এবং স্থানীয় পাহাড়ি ছড়ার দূষণ রোধ হচ্ছে।',
                ],
            ],
            'ratargul-swamp-forest-community-conservation-tours-expand' => [
                'রাতারগুল সোয়াম্প ফরেস্টে স্থানীয় মাঝিদের সমন্বয়ে পরিচালিত হচ্ছে পরিবেশবান্ধব পর্যটন',
                'শব্দদূষণমুক্ত বৈঠা চালিত নৌকায় বনভ্রমণ এবং সংরক্ষিত জলজ উদ্ভিদের সুরক্ষায় জোর দিচ্ছেন বনকর্মীরা।',
                [
                    'সিলেটের গোয়াইনঘাটের রাতারগুল মিঠাপানির জলাবনে প্রকৃতি ও জীববৈচিত্র্য রক্ষায় বিশেষ পরিবেশবান্ধব নির্দেশনা কার্যকর করা হয়েছে।',
                    'স্থানীয় মাঝিদের প্রশিক্ষিত ইকো-গাইড হিসেবে দায়িত্ব দিয়ে পর্যটকদের সচেতনতার সঙ্গে বন পরিদর্শনের সুযোগ দেয়া হচ্ছে।',
                    'পর্যটন থেকে অর্জিত আয়ের একটি অংশ সরাসরি স্থানীয় স্কুল ও বন সংরক্ষণের চারা রোপণ তহবিলে জমা হচ্ছে।',
                ],
            ],
            'haor-flood-resilient-raised-homestead-clusters-inaugurated' => [
                'সুনামগঞ্জের হাওরাঞ্চলে বন্যাসহনশীল উঁচু ভিটার টেকসই গ্রাম উদ্বোধন',
                'ঢেউ প্রতিরোধক প্রতিরক্ষা দেয়াল ও সৌরবিদ্যুৎ সুবিধায় আকস্মিক পাহাড়ি ঢল ও বন্যায়ও নিরাপদ থাকছে হাওরবাসী।',
                [
                    'দুর্যোগ ব্যবস্থাপনা অধিদপ্তরের উদ্যোগে সুনামগঞ্জের বিস্তীর্ণ হাওরে মাটি ভরাট করে উঁচু আশ্রয়গ্রাম নির্মাণ করা হয়েছে।',
                    'প্রতিটি গ্রামে মজবুত আরসিসি ব্লক দিয়ে আফাল (বড় ঢেউ) প্রতিরোধী বাঁধ এবং নিরাপদ সুপেয় পানির গভীর নলকূপ স্থাপন করা হয়েছে।',
                    'হাওরবাসী জানিয়েছেন, এখন বন্যার সময়ও তাদের বসতবাড়ি, গবাদিপশু ও ফলানো বোরো ধান পুরোপুরি নিরাপদ থাকে।',
                ],
            ],

            // Politics: Parliament
            'parliament-passes-digital-public-service-reform-bill' => [
                'ডিজিটাল নাগরিক সেবা নিশ্চিতকরণ বিল জাতীয় সংসদে পাস',
                'নতুন আইনে নির্দিষ্ট সময়ে নাগরিক সেবা প্রদান, স্বয়ংক্রিয় অভিযোগ নিষ্পত্তি ও উন্মুক্ত তথ্য ব্যবস্থা চালু হচ্ছে।',
                [
                    'সরকারি নাগরিক সেবা ডিজিটাল ও সহজতর করার লক্ষ্যে জাতীয় সংসদে যুগান্তকারী একটি বিল সর্বসম্মতিক্রমে পাস হয়েছে।',
                    'নতুন কাঠামোর আওতায় জন্মনিবন্ধন, জমির খতিয়ান ও নাগরিক সনদ প্রদানে সুনির্দিষ্ট সময়সীমা মানা বাধ্যতামূলক করা হয়েছে।',
                    'সংসদ সদস্যরা ব্যক্তিগত তথ্যের নিরাপত্তা বিধান এবং স্বচ্ছ জবাবদিহিতা নিশ্চিত করার বিষয়টিকে স্বাগত জানিয়েছেন।',
                ],
            ],
            'all-party-climate-caucus-calls-for-delta-funding' => [
                'ডেল্টা সুরক্ষায় জলবায়ু তহবিলের অনুদান বাড়ানোর দাবি সর্বদলীয় ফোরামের',
                'নদীভাঙন ও উপকূল রক্ষায় ঋণের পরিবর্তে সরাসরি আন্তর্জাতিক জলবায়ু অনুদান নিশ্চিতের আহ্বান।',
                [
                    'নদীর বাঁধ সুরক্ষা ও উপকূলীয় জনগোষ্ঠীর পুনর্বাসনে আন্তর্জাতিক অনুদান বৃদ্ধির আহ্বান জানিয়েছে সংসদীয় জলবায়ু ককাস।',
                    'যৌথ ঘোষণায় অভিযোজন পরিকল্পনায় বাংলাদেশের অগ্রণী ভূমিকার কথা স্মরণ করিয়ে দিয়ে দুর্যোগপ্রবণ এলাকা রক্ষায় তহবিল দ্রুত ছাড়ের দাবি জানানো হয়।',
                    'আসন্ন আন্তর্জাতিক জলবায়ু অর্থায়ন গোলটেবিল বৈঠকে এই প্রস্তাবনা তুলে ধরবেন সংসদীয় প্রতিনিধিদল।',
                ],
            ],
            'parliamentary-standing-committee-reviews-higher-education-budget' => [
                'উচ্চশিক্ষায় উদ্ভাবন ও গবেষণার বরাদ্দ বাড়ানোর সুপারিশ সংসদীয় কমিটির',
                'বিশ্ববিদ্যালয়গুলোতে রোবটিক্স, বায়োটেকনোলজি ও ন্যানোটেকনোলজি ল্যাব স্থাপনে বিশেষ তহবিলের প্রস্তাব।',
                [
                    'শিক্ষা মন্ত্রণালয় সম্পর্কিত সংসদীয় স্থায়ী কমিটি দেশের পাবলিক বিশ্ববিদ্যালয়গুলোর গবেষণার সুযোগ-সুবিধা আধুনিকায়নের বিষয়ে পর্যালোচনা করেছে।',
                    'কমিটির সদস্যরা স্থানীয় শিল্প ও কৃষির চাহিদার সঙ্গে সামঞ্জস্য রেখে যুগোপযোগী গবেষণা পরিচালনার ওপর জোর দেন।',
                    'মেধাবী শিক্ষার্থী ও তরুণ শিক্ষকদের পেটেন্ট এবং উদ্ভাবনী প্রকল্পের জন্য জাতীয় ইনোভেশন গ্র্যান্ট চালুর সুপারিশ করা হয়েছে।',
                ],
            ],

            // Politics: Elections
            'election-commission-upgrades-biometric-voter-verification' => [
                'নির্বাচন কমিশনের আধুনিক বায়োমেট্রিক ভোটার শনাক্তকরণ প্রযুক্তি',
                'আসন্ন নির্বাচনে নির্ভুল, দ্রুত ও নিরাপদ ভোটগ্রহণ নিশ্চিত করতে আধুনিক ডিভাইস প্রস্তুত করা হয়েছে।',
                [
                    'নির্বাচন কমিশন মাঠপর্যায়ে দ্রুত ভোটার যাচাইয়ের জন্য আধুনিক বায়োমেট্রিক কিট উন্মোচন করেছে।',
                    'নতুন পোর্টেবল ডিভাইসগুলোতে উন্নত এনক্রিপশন, উচ্চ রেজল্যুশনের আঙুলের ছাপ সেন্সর এবং দীর্ঘস্থায়ী ব্যাটারি রয়েছে।',
                    'নাগরিক প্রতিনিধি ও রাজনৈতিক দলগুলোর উপস্থিতিতে বিভাগীয় পর্যায়ে সফলভাবে এর কার্যকারিতা প্রদর্শন করা হয়।',
                ],
            ],
            'delimitation-of-constituencies-finalized-with-gis-mapping' => [
                'জিআইএস স্যাটেলাইট ডেটা ব্যবহার করে সংসদীয় আসনের সীমানা নির্ধারণ চূড়ান্ত',
                'ভোটার সংখ্যার ভারসাম্য ও প্রশাসনিক সুবিধার সমন্বয়ে আধুনিক প্রযুক্তির সাহায্যে প্রস্তুত করা হয়েছে ডিজিটাল ম্যাপ।',
                [
                    'নির্বাচন কমিশন ভৌগোলিক তথ্য ব্যবস্থা (জিআইএস) প্রযুক্তি ব্যবহার করে সংসদীয় নির্বাচনী এলাকার সীমানা হালনাগাদ করেছে।',
                    'জনশুমারি ও ভোটার বৃদ্ধির তথ্য বিবেচনায় নিয়ে যোগাযোগ ব্যবস্থার সুবিধার্থে মানচিত্র প্রস্তুত করা হয়েছে।',
                    'নাগরিকদের অবগতির জন্য বিস্তারিত সীমানা বিবরণী ও ম্যাপ নির্বাচন কমিশনের অনলাইন পোর্টালে উন্মুক্ত করা হয়েছে।',
                ],
            ],

            // Politics: Government
            'local-government-dialogue-focuses-on-civic-transparency' => [
                'স্থানীয় সরকার সংলাপে অংশীদারত্বমূলক বাজেট স্বচ্ছতার ওপর জোর',
                'ইউনিয়ন পরিষদ ও নাগরিক প্রতিনিধিদের অংশগ্রহণে তৃণমূলের উন্নয়ন পরিকল্পনার পর্যালোচনা।',
                [
                    'তৃণমূল প্রশাসনে নাগরিক অংশগ্রহণ নিশ্চিত করতে সারা দেশের পৌর মেয়র ও ইউপি চেয়ারম্যানদের নিয়ে জাতীয় সংলাপ অনুষ্ঠিত হয়েছে।',
                    'গ্রামাঞ্চলের সড়ক, ড্রেনেজ ও সড়কবাতি স্থাপনে জনগণের উন্মুক্ত মতামতের ভিত্তিতে বাজেট বরাদ্দের উদাহরণ তুলে ধরা হয়।',
                    'মন্ত্রণালয়ের পক্ষ থেকে জানানো হয়েছে, বার্ষিক উন্নয়ন সহায়তা তহবিলের বরাদ্দ নাগরিক সম্পৃক্ততার ওপর ভিত্তি করে নির্ধারণ করা হবে।',
                ],
            ],
            'public-administration-adopts-paperless-smart-governance-cloud' => [
                'কাগজবিহীন স্মার্ট প্রশাসনের আওতায় এলো সরকারি সব মন্ত্রণালয় ও দপ্তর',
                'সার্বভৌম ক্লাউড নেটওয়ার্কের মাধ্যমে নথির দ্রুত নিষ্পত্তি এবং প্রতিটি ফাইলের অগ্রগতি সহজে ট্র্যাকিংয়ের ব্যবস্থা।',
                [
                    'জনপ্রশাসন মন্ত্রণালয় সরকারি সিদ্ধান্ত গ্রহণ প্রক্রিয়া দ্রুত করতে আধুনিক ই-নথি সিস্টেম সর্বস্তরে বাধ্যতামূলক করেছে।',
                    'উচ্চপদস্থ কর্মকর্তারা এখন নিরাপদ এনক্রিপ্টেড ট্যাবলেটের মাধ্যমে যেকোনো স্থান থেকে ডিজিটাল স্বাক্ষর দিয়ে নথি অনুমোদন করছেন।',
                    'এর ফলে বিপুল পরিমাণ কাগজের সাশ্রয় হচ্ছে এবং সাধারণ মানুষের ফাইলের নিষ্পত্তির সময় অর্ধেকে নেমে এসেছে।',
                ],
            ],

            // Politics: Parties
            'civic-forums-engage-political-leaders-on-youth-manifesto' => [
                'যুব নীতি সংলাপে অংশ নিলেন বিভিন্ন রাজনৈতিক দলের নীতিনির্ধারকেরা',
                'কর্মসংস্থান, তথ্যপ্রযুক্তি প্রশিক্ষণ ও পরিবেশ সুরক্ষায় তারুণ্যের প্রত্যাশা তুলে ধরলেন বিশ্ববিদ্যালয় শিক্ষার্থীরা।',
                [
                    'এক অরাজনৈতিক জাতীয় যুব ফোরামে রাজনৈতিক দলগুলোর প্রতিনিধিদের সাথে তরুণ প্রজন্মের খোলামেলা মতবিনিময় অনুষ্ঠিত হয়েছে।',
                    'শিক্ষার্থীরা পরিবেশবান্ধব সবুজ অর্থনীতি ও তরুণ উদ্যোক্তাদের জন্য সহজ শর্তের ঋণ প্রদানের দাবি জানান।',
                    'রাজনৈতিক নেতৃবৃন্দ তরুণদের সুপারিশমালাকে স্বাগত জানিয়ে আগামী দলীয় ইশতেহারে তা অন্তর্ভুক্ত করার প্রতিশ্রুতি দেন।',
                ],
            ],

            // World: Asia
            'river-research-maps-seasonal-change' => [
                'মৌসুমি নদীর পরিবর্তন মানচিত্রে তুলে ধরছেন গবেষকেরা',
                'উপগ্রহ পর্যবেক্ষণ ও নদীপারের মানুষের তথ্য মিলিয়ে একটি নতুন উন্মুক্ত উপাত্তভান্ডার তৈরি হয়েছে।',
                [
                    'গবেষকেরা মৌসুমভেদে নদীর গতিপথ ও আশপাশের বসতি কীভাবে বদলায় তার উন্মুক্ত উপাত্ত প্রকাশ করেছেন।',
                    'প্রকল্পটিতে উপগ্রহচিত্রের সঙ্গে স্কুল ও স্থানীয় সংগঠনের পর্যবেক্ষণ যুক্ত করা হয়েছে।',
                    'এই তথ্য ভাঙনপ্রবণ এলাকায় নিরাপদ অবকাঠামো ও উন্নত পরিকল্পনায় সহায়তা করবে বলে আশা করা হচ্ছে।',
                ],
            ],
            'bangladesh-japan-trade-pact-enters-final-review' => [
                'বাংলাদেশ-জাপান অর্থনৈতিক অংশীদারত্ব চুক্তি চূড়ান্ত পর্যালোচনায়',
                'শুল্কমুক্ত রপ্তানি, প্রযুক্তিগত সহযোগিতা ও দক্ষ জনশক্তি বিনিময় নিয়ে আলোচনা শেষ পর্যায়ে।',
                [
                    'টোকিও ও ঢাকায় দ্বিপক্ষীয় মুক্তবাণিজ্য ও অর্থনৈতিক অংশীদারত্ব চুক্তির খসড়া নিয়ে চূড়ান্ত আলোচনা সম্পন্ন হয়েছে।',
                    'চুক্তির ফলে শিল্পোৎপাদন, অটোমোবাইল প্রযুক্তি ও নবায়নযোগ্য শক্তিতে জাপানি বিনিয়োগের অপার সুযোগ সৃষ্টি হবে।',
                    'উভয় দেশের শীর্ষ ব্যবসায়িক প্রতিনিধিরা চুক্তিটি দ্রুত স্বাক্ষরের মাধ্যমে দ্বিপক্ষীয় বাণিজ্য সম্প্রসারণের প্রত্যাশা করছেন।',
                ],
            ],
            'south-asian-regional-power-grid-trials-begin' => [
                'দক্ষিণ এশিয়ায় আঞ্চলিক পরিবেশবান্ধব বিদ্যুৎ সঞ্চালন গ্রিডের সফল পরীক্ষা',
                'হিমালয়ের জলবিদ্যুৎ ও উপকূলীয় বিদ্যুতের মৌসুমভিত্তিক আদান-প্রদানে নতুন দিগন্ত।',
                [
                    'আঞ্চলিক উচ্চক্ষমতাসম্পন্ন ক্রস-বর্ডার গ্রিড লাইনে সফলভাবে পরীক্ষামূলক বিদ্যুৎ সঞ্চালন শুরু হয়েছে।',
                    'এই উদ্যোগের ফলে গ্রীষ্মকালে নেপাল ও ভুটানের উদ্বৃত্ত জলবিদ্যুৎ বাংলাদেশের শিল্পাঞ্চলের চাহিদা মেটাতে ব্যবহৃত হবে।',
                    'জ্বালানি বিশেষজ্ঞরা বলছেন, এই সংযোগ দক্ষিণ এশিয়ার সার্বিক কার্বন নিঃসরণ হ্রাস এবং টেকসই জ্বালানি নিরাপত্তায় বড় মাইলফলক।',
                ],
            ],
            'asean-bangladesh-digital-trade-connectivity-forum-concludes' => [
                'আসিয়ান ও বাংলাদেশের মধ্যে ডিজিটাল বাণিজ্য ও সেমিকন্ডাক্টর সরবরাহ সম্মেলন সম্পন্ন',
                'সিঙ্গাপুর, মালয়েশিয়া ও ভিয়েতনামের সাথে সফটওয়্যার ও হার্ডওয়্যার শিল্পে যৌথ অংশীদারত্বের বিপুল সম্ভাবনা।',
                [
                    'ঢাকায় আয়োজিত দুই দিনব্যাপী আন্তর্জাতিক বাণিজ্য সম্মেলনে আসিয়ান দেশগুলোর প্রতিনিধিরা যোগ দেন।',
                    'ডিজিটাল পেমেন্ট গেটওয়ে ও ক্লাউড প্রযুক্তিতে আঞ্চলিক সমন্বয় গড়ে তোলার বিষয়ে ফলপ্রসূ আলোচনা অনুষ্ঠিত হয়।',
                    'অর্থনীতিবিদরা জানিয়েছেন, দক্ষিণ-পূর্ব এশিয়ার বাজারে দেশীয় প্রযুক্তি সেবা রপ্তানির চমৎকার সুযোগ তৈরি হয়েছে।',
                ],
            ],

            // World: Europe
            'eu-approves-extended-gsp-plus-market-access-roadmap' => [
                'ইউরোপীয় ইউনিয়নে বাংলাদেশের জন্য জিএসপি প্লাস শুল্কমুক্ত সুবিধার রূপরেখা চূড়ান্ত',
                'পরিবেশবান্ধব সবুজ কারখানা ও শ্রম অধিকারের ধারাবাহিক উন্নতিতে ইউরোপের বাজারে তৈরি পোশাকের রপ্তানি সুরক্ষিত।',
                [
                    'ব্রাসেলস ও ঢাকার মধ্যকার ফলপ্রসূ বৈঠকে ইউরোপের বাজারে বাংলাদেশের দীর্ঘমেয়াদি বাণিজ্য সুবিধার রোডম্যাপ অনুমোদিত হয়েছে।',
                    'আন্তর্জাতিক ক্রেতারা পরিবেশবান্ধব সনদপ্রাপ্ত বাংলাদেশের টেক্সটাইল কারখানার উচ্চমানের ভূয়সী প্রশংসা করেছেন।',
                    'বাণিজ্য মন্ত্রণালয় জানিয়েছে, এই চুক্তির ফলে ইউরোপের শীর্ষ ব্র্যান্ডগুলোতে বাংলাদেশের রপ্তানি প্রবৃদ্ধি অব্যাহত থাকবে।',
                ],
            ],
            'scandinavian-clean-energy-consortium-funds-bay-wind-study' => [
                'বঙ্গোপসাগরে অফশোর বায়ুবিদ্যুৎ সম্ভাব্যতা যাচাইয়ে কাজ শুরু করেছে নর্ডিক কনসোর্টিয়াম',
                'সমুদ্রের প্রবল বাতাসকে কাজে লাগিয়ে হাজার মেগাওয়াট পরিচ্ছন্ন বিদ্যুৎ উৎপাদনের প্রাথমিক সমীক্ষা চলমান।',
                [
                    'স্ক্যান্ডিনেভিয়ান শীর্ষ বিদ্যুৎ কোম্পানির প্রকৌশলীরা বঙ্গোপসাগরের উপকূলীয় অঞ্চলে বায়ুবিদ্যুতের উপাত্ত সংগ্রহ করছেন।',
                    'সমুদ্রপৃষ্ঠের নিয়মিত বাতাসের গতিবেগ বিশ্লেষণ করে অফশোর উইন্ড টারবাইন স্থাপনের সম্ভাব্যতা খতিয়ে দেখা হচ্ছে।',
                    'পরিবেশ গবেষকরা নিশ্চিত করেছেন, এই প্রকল্প সামুদ্রিক জীববৈচিত্র্য ও মৎস্য সম্পদের কোনো ক্ষতি না করেই বাস্তবায়িত হবে।',
                ],
            ],

            // World: Americas
            'un-climate-summit-agrees-on-new-loss-and-damage-facility' => [
                'জাতিসংঘ জলবায়ু সম্মেলনে লস অ্যান্ড ড্যামেজ তহবিলের নীতিমালায় সম্মতি',
                'ঝুঁকিপূর্ণ ডেল্টা ও দ্বীপরাষ্ট্রগুলোর জরুরি পুনর্বাসনে বিশেষ আর্থিক সুবিধার দ্বার উন্মোচিত।',
                [
                    'আন্তর্জাতিক জলবায়ু সম্মেলনে ক্ষতিগ্রস্ত দেশগুলোর দুর্যোগ পরবর্তী সহায়তার জন্য লস অ্যান্ড ড্যামেজ ফান্ডের নিয়মাবলি চূড়ান্ত হয়েছে।',
                    'এই কাঠামোর মাধ্যমে চরম আবহাওয়াজনিত ক্ষয়ক্ষতি ও সমুদ্রপৃষ্ঠের উচ্চতা বৃদ্ধিতে ক্ষতিগ্রস্ত এলাকায় দ্রুত অর্থ পৌঁছানো সম্ভব হবে।',
                    'স্বল্পোন্নত ও জলবায়ু ঝুঁকিপূর্ণ দেশগুলোর পক্ষে বাংলাদেশের বলিষ্ঠ নেতৃত্বের প্রশংসা করেছেন আন্তর্জাতিক প্রতিনিধিরা।',
                ],
            ],
            'north-american-universities-partner-with-bangladesh-on-ai' => [
                'যুক্তরাষ্ট্র ও কানাডার শীর্ষ বিশ্ববিদ্যালয়ের সঙ্গে যৌথ এআই গবেষণা ল্যাব চালু',
                'বাংলা প্রাকৃতিক ভাষা প্রক্রিয়াকরণ ও বন্যা পূর্বাভাসের কৃত্রিম বুদ্ধিমত্তা অ্যালগরিদম নিয়ে কাজ করবেন গবেষকেরা।',
                [
                    'বুয়েট ও ঢাকা বিশ্ববিদ্যালয়ের সাথে উত্তর আমেরিকার প্রখ্যাত প্রকৌশল বিশ্ববিদ্যালয়গুলোর যৌথ গবেষণা চুক্তি সই হয়েছে।',
                    'এই উদ্যোগের আওতায় দেশীয় গবেষকেরা সুপারকম্পিউটার ক্লাস্টার ব্যবহার করে বৈজ্ঞানিক গবেষণার সুযোগ পাবেন।',
                    'কৃষিতে রোগবালাই দমন এবং স্যাটেলাইট ডেটা বিশ্লেষণের নতুন মডেল তৈরিতে এই ল্যাব গুরুত্বপূর্ণ ভূমিকা রাখবে।',
                ],
            ],

            // World: Middle East
            'gulf-cooperation-council-expands-skilled-workforce-agreements' => [
                'উপসাগরীয় দেশগুলোতে দক্ষ কারিগরি ও স্বাস্থ্যকর্মী নিয়োগের নতুন চুক্তি',
                'নার্স, আইটি বিশেষজ্ঞ ও সার্টিফাইড ইঞ্জিনিয়ারদের জন্য উন্মুক্ত হলো উপসাগরীয় অঞ্চলের আধুনিক শ্রমবাজার।',
                [
                    'প্রবাসী কল্যাণ মন্ত্রণালয় জিসিসিভুক্ত দেশগুলোর সাথে উচ্চ দক্ষতাসম্পন্ন জনশক্তি প্রেরণের চুক্তি সম্পন্ন করেছে।',
                    'নতুন নীতিমালার ফলে নার্সিং ও কারিগরি ডিপ্লোমাধারীরা আন্তর্জাতিক মানের বেতন ও কাজের সুরক্ষা পাবেন।',
                    'বিশেষজ্ঞরা বলছেন, দক্ষ পেশাজীবীদের প্রেরণের মাধ্যমে বৈদেশিক রেমিট্যান্সের পরিমাণ বহুগুণ বৃদ্ধি পাবে।',
                ],
            ],
            'saudi-bangladesh-solar-infrastructure-investment-signed' => [
                'সৌদি বিদ্যুৎ কোম্পানির অর্থায়নে চট্টগ্রামে স্থাপিত হচ্ছে বড় সোলার পার্ক',
                'জাতীয় গ্রিডে যুক্ত হবে কয়েকশ মেগাওয়াট নবায়নযোগ্য সৌরবিদ্যুৎ; চুক্তি সই সম্পন্ন।',
                [
                    'রিয়াদে আয়োজিত দ্বিপক্ষীয় বিনিয়োগ ফোরামে বাংলাদেশের বিদ্যুৎ খাতের এই বৃহৎ চুক্তি চূড়ান্ত হয়েছে।',
                    'উপকূলীয় এলাকার পতিত জমিতে আধুনিক বাইফেসিয়াল সোলার প্যানেল ব্যবহার করে বিদ্যুৎ কেন্দ্রটি নির্মিত হবে।',
                    'প্রকল্প পরিচালক জানিয়েছেন, আগামী ১৮ মাসের মধ্যে এই সৌর বিদ্যুৎকেন্দ্র থেকে বাণিজ্যিক সরবরাহ শুরু হবে।',
                ],
            ],

            // Business: Economy
            'aman-harvest-reaches-local-markets' => [
                'আমনের ভালো ফলন স্থানীয় বাজারে পৌঁছাতে শুরু করেছে',
                'সেচ ব্যবস্থাপনার সুফলে উত্তরের কয়েকটি জেলার কৃষকেরা ভালো ফলনের কথা জানিয়েছেন।',
                [
                    'উত্তরের জেলাগুলোতে আমন ধান কাটা শেষ হওয়ার সঙ্গে সঙ্গে নতুন চাল আঞ্চলিক বাজারে আসছে।',
                    'কৃষি কর্মকর্তারা জানান, স্থানীয় সেচ পরিকল্পনা ও সময়মতো পরামর্শ পরিবর্তনশীল আবহাওয়ায় ফসল রক্ষায় সহায়তা করেছে।',
                    'খামার থেকে মিল ও খুচরা বাজারে ধান পৌঁছানোর সময় পরিবহন ও সংরক্ষণ ব্যয় পর্যবেক্ষণ করা হচ্ছে।',
                ],
            ],
            'leather-and-footwear-exports-post-double-digit-growth' => [
                'চামড়া ও জুতা রপ্তানিতে দুই অঙ্কের প্রবৃদ্ধি অর্জিত',
                'পরিবেশবান্ধব ট্যানারি কমপ্লায়েন্স ও আন্তর্জাতিক সার্টিফিকেশনের ফলে ইউরোপ-এশিয়ায় নতুন বাজার সৃষ্টি।',
                [
                    'রপ্তানি উন্নয়ন ব্যুরোর হালনাগাদ তথ্যে দেখা গেছে, চামড়াজাত পণ্য ও জুতা রপ্তানিতে উল্লেখযোগ্য প্রবৃদ্ধি হয়েছে।',
                    'শিল্প উদ্যোক্তারা জানান, কেন্দ্রীয় বর্জ্য শোধনাগারের আধুনিকায়ন ও টেকসই উৎপাদন পদ্ধতির কারণে আন্তর্জাতিক ক্রেতাদের আস্থা বাড়ছে।',
                    'বিশ্বমানের পণ্য তৈরিতে দেশীয় ডিজাইনার ও কারিগরদের জন্য বিশেষ প্রশিক্ষণ একাডেমি চালু করা হচ্ছে।',
                ],
            ],
            'export-diversification-fund-boosts-electronics-manufacturing' => [
                'রপ্তানি বহুমুখীকরণ তহবিলের সহায়তায় বাড়ছে ইলেকট্রনিক্স হোম অ্যাপ্লায়েন্স রপ্তানি',
                'গাজীপুর ও নরসিংদীর কারখানায় তৈরি ফ্রিজ, টিভি ও কম্প্রেসার রপ্তানি হচ্ছে মধ্যপ্রাচ্য ও ইউরোপের বাজারে।',
                [
                    'বাণিজ্য মন্ত্রণালয়ের বিশেষ সহায়তায় ভারী ইলেকট্রনিক্স ও প্রযুক্তিপণ্য তৈরিতে দেশীয় ব্র্যান্ডগুলো অভাবনীয় সাফল্য দেখাচ্ছে।',
                    'উন্নত রোবটিক্স লাইনে উৎপাদিত বিদ্যুৎসাশ্রয়ী যন্ত্রপাতি আন্তর্জাতিক গুণগত মান নিশ্চিত করে বিদেশে পাঠানো হচ্ছে।',
                    'শিল্প খাত সংশ্লিষ্টরা আশা করছেন, তৈরি পোশাকের পাশাপাশি ইলেকট্রনিক্স শিল্প দেশের প্রধান রপ্তানি খাত হিসেবে গড়ে উঠবে।',
                ],
            ],

            // Business: Banking
            'central-bank-eases-remittance-transfer-protocols' => [
                'প্রবাসীদের রেমিট্যান্স প্রেরণে সহজ ডিজিটাল সুবিধা চালু করল কেন্দ্রীয় ব্যাংক',
                'বৈধ চ্যানেলে তাৎক্ষণিক প্রণোদনা ও নামমাত্র খরচে প্রবাসীদের কষ্টার্জিত অর্থ দেশে পাঠানোর সুযোগ।',
                [
                    'প্রবাসী কর্মীদের জন্য সহজ শর্তে ডিজিটাল ওয়ালেটের মাধ্যমে রেমিট্যান্স পাঠানোর নতুন নীতিমালা জারি করেছে বাংলাদেশ ব্যাংক।',
                    'যাচাইকৃত ব্যাংকিং অ্যাপ ব্যবহারে সরকারি নগদ প্রণোদনা কোনো মধ্যস্বত্বভোগী ছাড়াই সরাসরি ব্যাংক অ্যাকাউন্টে জমা হবে।',
                    'বাণিজ্যিক ব্যাংকগুলো আশা করছে, এই পদক্ষেপের ফলে ব্যাংকিং চ্যানেলে বৈদেশিক মুদ্রার প্রবাহ উল্লেখযোগ্য হারে বাড়বে।',
                ],
            ],
            'mobile-financial-services-reach-remote-char-islands' => [
                'যমুনার দুর্গম চরাঞ্চলে পৌঁছেছে এজেন্ট ব্যাংকিং ও মোবাইল ফিন্যান্সিয়াল সার্ভিস',
                'সৌরবিদ্যুৎচালিত ডিজিটাল পয়েন্টের মাধ্যমে চরের মানুষ পাচ্ছেন সঞ্চয়, কৃষিঋণ ও ভাতা প্রাপ্তির সুবিধা।',
                [
                    'পদ্মা ও যমুনা নদীর দুর্গম চরাঞ্চলে আর্থিক সেবা পৌঁছে দিতে বিশেষ এজেন্ট ব্যাংকিং কিয়স্ক চালু করা হয়েছে।',
                    'চরের কৃষকেরা এখন দীর্ঘ ও ঝুঁকিপূর্ণ নৌভ্রমণ ছাড়াই সরাসরি সরকারি কৃষিভাতা ও রেমিট্যান্সের অর্থ গ্রহণ করছেন।',
                    'স্থানীয় নারীদের জন্য আয়োজিত আর্থিক সাক্ষরতা কর্মশালার মাধ্যমে ক্ষুদ্র ব্যবসার সহজ শর্তের ঋণ প্রদান করা হচ্ছে।',
                ],
            ],
            'green-banking-refinance-facility-disburses-record-credit' => [
                'সবুজ শিল্পায়নে কেন্দ্রীয় ব্যাংকের টেকসই অর্থায়ন তহবিলের রেকর্ড ঋণ বিতরণ',
                'বর্জ্য শোধনাগার, সৌরবিদ্যুৎ ও কার্বন নিঃসরণ হ্রাসে স্বল্প সুদে পুনঃঅর্থায়ন সুবিধা নিচ্ছেন শিল্পোদ্যোক্তারা।',
                [
                    'বাংলাদেশ ব্যাংক প্রকাশিত টেকসই অর্থায়ন প্রতিবেদনে দেখা গেছে, সবুজ কারখানা রূপান্তরে উদ্যোক্তাদের আগ্রহ তুঙ্গে।',
                    'কম সুদের এই তহবিল ব্যবহার করে কলকারখানাগুলোতে পানি পুনর্ব্যবহার ও আধুনিক তাপ সংরক্ষণ বয়লার বসানো হচ্ছে।',
                    'ব্যাংকাররা জানিয়েছেন, পরিবেশবান্ধব কারখানা আন্তর্জাতিক ক্রেতাদের কাছে অগ্রাধিকার পাওয়ায় ব্যবসা আরও টেকসই হচ্ছে।',
                ],
            ],

            // Business: Markets
            'jute-diversified-products-gain-surging-demand-in-global-markets' => [
                'বিশ্বজুড়ে প্লাস্টিক বর্জনের ফলে বেড়েছে বহুমুখী পাটপণ্যের আকাশচুম্বী চাহিদা',
                'ফরিদপুর ও খুলনার কারখানায় তৈরি সোনালী আঁশের পরিবেশবান্ধব ব্যাগ ও হোম ডেকর রপ্তানি হচ্ছে ইউরোপে।',
                [
                    'আন্তর্জাতিক পর্যায়ে পলিথিনের ব্যবহার নিষিদ্ধ হওয়ায় পাটজাত শপিং ব্যাগ ও জিও-টেক্সটাইলের চাহিদা বহুগুণ বেড়েছে।',
                    'পাটকল ও কুটির শিল্প উদ্যোক্তারা পরিবেশবান্ধব নিত্যব্যবহার্য পণ্য তৈরিতে নতুন নতুন ডিজাইন নিয়ে আসছেন।',
                    'কৃষিবিজ্ঞানীরা জানিয়েছেন, দ্রুত জাগ দেওয়ার উপযোগী উন্নত পাটের জাত চাষ করায় চাষিরা ভালো লাভ পাচ্ছেন।',
                ],
            ],
            'commodity-exchange-platform-launches-for-agricultural-futures' => [
                'কৃষিপণ্যের ন্যায্যমূল্য নিশ্চিত করতে দেশে প্রথম কমোডিটি এক্সচেঞ্জের পরীক্ষামূলক কার্যক্রম',
                'ডিজিটাল ওয়্যারহাউস রসিদের মাধ্যমে কৃষকেরা মধ্যস্বত্বভোগী ছাড়াই ফসল কেনাবেচা ও ব্যাংক ঋণ নিতে পারছেন।',
                [
                    'বাংলাদেশ সিকিউরিটিজ অ্যান্ড এক্সচেঞ্জ কমিশন ও কৃষি মন্ত্রণালয়ের যৌথ উদ্যোগে কৃষিপণ্য কেনাবেচার প্ল্যাটফর্ম চালু হয়েছে।',
                    'আধুনিক সাইলোতে সংরক্ষিত শস্যের ডিজিটাল রসিদ ব্যবহার করে কৃষকেরা সুবিধাজনক সময়ে ভালো দামে ফসল বিক্রি করছেন।',
                    'অর্থনীতিবিদরা বলছেন, এই ব্যবস্থা পণ্যের মৌসুমি দামের ওঠানামা কমিয়ে ভোক্তা ও কৃষক উভয়কেই সুরক্ষিত রাখবে।',
                ],
            ],

            // Business: Industry
            'startups-secure-regional-seed-investments-in-agritech' => [
                'আন্তর্জাতিক বিনিয়োগ পেল দেশের দুই কৃষিপ্রযুক্তি স্টার্টআপ',
                'কৃষকদের ডেটাভিত্তিক পরামর্শ ও কোল্ড-চেইন সরবরাহ নিশ্চিতে তৈরি অ্যাপে আস্থা রাখছেন বিদেশি বিনিয়োগকারীরা।',
                [
                    'বাংলাদেশের দুটি সম্ভাবনাময় এগ্রিটেক স্টার্টআপ আঞ্চলিক বিনিয়োগ তহবিল থেকে সিড রাউন্ডের মূলধন সংগ্রহ করেছে।',
                    'তাদের প্ল্যাটফর্ম স্যাটেলাইট চিত্র ও মাটির সেন্সর বিশ্লেষণ করে কৃষকদের সুনির্দিষ্ট সার ও সেচের পরামর্শ দেয়।',
                    'বিনিয়োগকারীরা বলছেন, ডেল্টা অঞ্চলে কৃষি আধুনিকায়নে তথ্যপ্রযুক্তির ব্যবহার দারুণ অর্থনৈতিক সম্ভাবনা তৈরি করেছে।',
                ],
            ],
            'pharmaceutical-active-ingredient-park-in-munshiganj-expands' => [
                'মুন্সীগঞ্জের এপিআই পার্কে শুরু হলো ওষুধের কাঁচামাল দেশীয় উৎপাদনের নতুন অধ্যায়',
                'আমদানি নির্ভরতা কমিয়ে দেশেই জীবনরক্ষাকারী ওষুধের অ্যাক্টিভ ফার্মাসিউটিক্যাল ইনগ্রেডিয়েন্ট তৈরি হচ্ছে।',
                [
                    'মুন্সীগঞ্জের গজারিয়ায় স্থাপিত ওষুধ শিল্প পার্কে নতুন সিন্থেটিক কেমিক্যাল প্ল্যান্টগুলোর উৎপাদন শুরু হয়েছে।',
                    'পরিবেশ সুরক্ষা নিশ্চিতে পার্কে স্থাপন করা হয়েছে আন্তর্জাতিক মানের সেন্ট্রাল এফ্লুয়েন্ট ট্রিটমেন্ট প্ল্যান্ট।',
                    'ওষুধ শিল্পের শীর্ষ নির্বাহীরা জানিয়েছেন, দেশীয় কাঁচামাল ব্যবহার করায় সাধারণ মানুষ আরও কম দামে ওষুধ পাবেন।',
                ],
            ],
            'shipbuilding-yards-secure-orders-for-hybrid-cargo-vessels' => [
                'ইউরোপের জন্য হাইব্রিড কার্গো জাহাজ নির্মাণের বড় রপ্তানি আদেশ পেল দেশীয় ডকইয়ার্ড',
                'জার্মানি ও নরওয়ের জন্য মেঘনার তীরে তৈরি হচ্ছে জ্বালানিসাশ্রয়ী ও পরিবেশবান্ধব কন্টেইনার জাহাজ।',
                [
                    'দেশের বেসরকারি জাহাজ নির্মাণ শিল্পে আধুনিক পরিবেশবান্ধব জাহাজ তৈরির নতুন নতুন বিদেশি ক্রয়াদেশ আসছে।',
                    'ব্যাটারি-সহায়তাপ্রাপ্ত ডুয়েল ফুয়েল ইঞ্জিনযুক্ত এসব জাহাজ আন্তর্জাতিক সমুদ্রপথে কম কার্বন নিঃসরণ করবে।',
                    'মেরিন ইঞ্জিনিয়াররা জানিয়েছেন, আন্তর্জাতিক ক্লাসিফিকেশন সোসাইটির কড়া মান বজায় রেখে জাহাজগুলো তৈরি হচ্ছে।',
                ],
            ],

            // Sports: Cricket
            'bangladesh-clinches-thrilling-t20-series-decider' => [
                'মিরপুরে শ্বাসরুদ্ধকর শেষ ওভারে সিরিজ জয় করল বাংলাদেশ',
                'ডেথ ওভারে দুর্দান্ত বোলিং ও মিডল অর্ডারের দায়িত্বশীল ব্যাটিংয়ে স্মরণীয় জয়।',
                [
                    'মিরপুর শের-ই-বাংলা জাতীয় ক্রিকেট স্টেডিয়ামে দর্শকদের উল্লাসে ভাসিয়ে সিরিজের শেষ ম্যাচে রুদ্ধশ্বাস জয় তুলে নিয়েছে টাইগাররা।',
                    'শেষ ওভারগুলোতে বোলারদের নিখুঁত লাইন-লেংথ ও বুদ্ধিদীপ্ত স্লোয়ার সফরকারী দলকে আটকে দেয়।',
                    'অধিনায়ক তরুণ ক্রিকেটারদের চাপের মুখে শান্ত থাকা এবং দলীয় পরিকল্পনা বাস্তবায়নের ভূয়সী প্রশংসা করেন।',
                ],
            ],
            'premier-league-cricket-features-promising-pace-discoveries' => [
                'ঢাকা প্রিমিয়ার লিগে নজর কাড়ছেন ঘণ্টায় ১৪০ কিলোমিটার গতির তরুণ পেসাররা',
                'ঘরোয়া ক্রিকেটে গতি ও বাউন্স দিয়ে ব্যাটারদের পরাস্ত করছেন নতুন প্রজন্মের উদীয়মান ফাস্ট বোলাররা।',
                [
                    'চলমান ঘরোয়া ক্রিকেট আসরে দেশের বিভিন্ন প্রান্ত থেকে উঠে আসা তরুণ পেস বোলাররা চমৎকার বোলিং প্রদর্শন করছেন।',
                    'বিসিবির হাইপারফরম্যান্স ক্যাম্পে আধুনিক অনুশীলনের ফলে বোলারদের ফিটনেস ও গতি উভয়ই বৃদ্ধি পেয়েছে।',
                    'জাতীয় নির্বাচকরা জানিয়েছেন, প্রতিভাবান পেসারদের বিশেষ পরিচর্যায় রেখে জাতীয় দলের জন্য প্রস্তুত করা হচ্ছে।',
                ],
            ],
            'bangladesh-under-19-cricket-team-triumphs-in-tri-nation-cup' => [
                'অপরাজিত থেকে ত্রিদেশীয় যুব কাপের চ্যাম্পিয়ন বাংলাদেশ অনূর্ধ্ব-১৯ দল',
                'অধিনায়কের অনবদ্য সেঞ্চুরি ও স্পিনারদের ঘূর্ণিজাদুতে ফাইনালে বড় ব্যবধানে জয়।',
                [
                    'আন্তর্জাতিক যুব ক্রিকেট টুর্নামেন্টের ফাইনালে অসাধারণ নৈপুণ্য প্রদর্শন করে ট্রফি ঘরে তুলল টাইগার যুবারা।',
                    'শীর্ষ সারির ব্যাটাররা দুর্দান্ত দায়িত্বশীলতা দেখিয়ে দলকে শক্ত ভিতের ওপর দাঁড় করিয়ে দেন।',
                    'ক্রিকেট বিশেষজ্ঞরা বলছেন, অনূর্ধ্ব-১৯ পর্যায়ের এই শক্ত পাইপলাইন ভবিষ্যৎ জাতীয় দলকে আরও শক্তিশালী করবে।',
                ],
            ],

            // Sports: Football
            'national-women-football-team-qualifies-for-asian-cup-stage' => [
                'ইতিহাস গড়ে এশিয়ান কাপের নকআউট পর্বে বাংলাদেশ নারী ফুটবল দল',
                'চমৎকার আক্রমণাত্মক খেলা এবং দৃঢ় রক্ষণভাগে গ্রুপ পর্বে অপরাজিত থাকার গৌরব।',
                [
                    'অপরাজিত থেকে আন্তর্জাতিক টুর্নামেন্টের নকআউট পর্বে স্থান করে নিয়ে ইতিহাস রচনা করেছে বাংলাদেশের মেয়েরা।',
                    '৯০ মিনিটের টানটান উত্তেজনার ম্যাচে দুর্দান্ত উইং প্লে এবং গোলরক্ষকের বীরত্বপূর্ণ সেভ দলকে অবিস্মরণীয় জয় এনে দেয়।',
                    'দেশজুড়ে সামাজিক যোগাযোগমাধ্যম ও রাজপথে নারী ফুটবলারদের এই অভাবনীয় সাফল্য উদযাপিত হচ্ছে।',
                ],
            ],
            'bangladesh-premier-league-football-records-highest-fan-turnout' => [
                'বিপিএল ফুটবলে দর্শকদের উপচে পড়া ভিড়, জমে উঠেছে মাঠের লড়াই',
                'বিভাগীয় স্টেডিয়ামগুলোতে স্থানীয় সমর্থকদের সমর্থন ও বিদেশি তারকা ফুটবলারদের নৈপুণ্যে ফুটবলে নতুন প্রাণ।',
                [
                    'ময়মনসিংহ, কুমিল্লা ও গোপালগঞ্জের ফুটবল স্টেডিয়ামগুলোতে প্রতিটি ম্যাচে হাজার হাজার দর্শক উপস্থিত থাকছেন।',
                    'ক্লাবগুলোর মধ্যে হাড্ডাহাড্ডি লড়াই ও জমজমাট আক্রমণাত্মক ফুটবল ম্যাচগুলোকে দারুণ উপভোগ্য করে তুলেছে।',
                    'ফুটবল ফেডারেশন জানিয়েছে, মাঠের নিয়মিত দর্শক উপস্থিতি ঘরোয়া ফুটবলের বাণিজ্যিক প্রসারে বড় ভূমিকা রাখছে।',
                ],
            ],
            'grassroots-youth-football-academies-launched-in-eight-divisions' => [
                'আট বিভাগে অনূর্ধ্ব-১৫ প্রতিভাবান ফুটবলারদের জন্য আবাসিক একাডেমি চালু',
                'তৃণমূল পর্যায় থেকে প্রতিভা অন্বেষণ করে পেশাদার বিদেশি কোচের অধীনে দেয়া হচ্ছে বিশেষ প্রশিক্ষণ।',
                [
                    'বাফুফের উদ্যোগে সারা দেশের স্কুল ও প্রত্যন্ত অঞ্চল থেকে বাছাই করা কিশোর ফুটবলারদের একাডেমিতে অন্তর্ভুক্ত করা হয়েছে।',
                    'খেলোয়াড়দের নিয়মিত আধুনিক ট্যাকটিক্যাল প্রশিক্ষণ ও পুষ্টি নিশ্চিত করার পাশাপাশি শিক্ষার সুবিধাও রাখা হয়েছে।',
                    'কোচেরা আশা করছেন, এই একাডেমি থেকে আগামী কয়েক বছরে জাতীয় দলের ভবিষ্যৎ তারকারা উঠে আসবেন।',
                ],
            ],

            // Sports: Local
            'district-kabaddi-championship-draws-huge-crowds-in-bogura' => [
                'বগুড়ায় ঐতিহ্যবাহী জেলা কাবাডি প্রতিযোগিতায় দর্শকের ঢল',
                'গ্রামাঞ্চলের ঐতিহ্যবাহী খেলায় অংশ নিচ্ছে তরুণ ও অভিজ্ঞ খেলোয়াড়দের একাধিক দল।',
                [
                    'ঐতিহ্যবাহী কাবাডি টুর্নামেন্টের ফাইনাল উপভোগ করতে বগুড়ার খেলার মাঠে দূর-দূরান্ত থেকে হাজারো ক্রীড়াপ্রেমী মানুষ সমবেত হন।',
                    'চমকপ্রদ রেইড ও ক্ষিপ্রগতির ট্যাকল খেলায় বাড়তি উত্তেজনা ছড়ায়।',
                    'আয়োজকেরা জানান, গ্রামীণ ঐতিহ্যবাহী খেলাধুলা বাঁচিয়ে রাখা তরুণ সমাজকে সুস্থ ও মাদকমুক্ত রাখতে গুরুত্বপূর্ণ ভূমিকা রাখছে।',
                ],
            ],
            'traditional-boat-race-nouka-baich-mesmerizes-thousands-on-surma' => [
                'সুরমা নদীর বুকে বর্ণিল নৌকাবাইচে হাজারো মানুষের আনন্দ-উচ্ছ্বাস',
                'পঞ্চাশটিরও বেশি বাহারি সাজের ছিপ ও কোষা নৌকার বৈঠার ছন্দে মেতে উঠল নদীপারের মানুষ।',
                [
                    'সিলেটের সুরমা নদীর দুই তীরে সমবেত হয়ে ঐতিহ্যবাহী নৌকাবাইচ উপভোগ করেন বিভিন্ন বয়সের মানুষ।',
                    'মাঝিদের গাওয়া জারি ও সারি গানের তালে তালে বৈঠার দ্রুত সঞ্চালনে নদীজুড়ে এক অপূর্ব দৃশ্যের অবতারণা হয়।',
                    'প্রতিযোগিতা শেষে বিজয়ী দলগুলোকে ঐতিহ্যবাহী পিতলের ট্রফি ও আকর্ষণীয় পুরস্কার প্রদান করা হয়।',
                ],
            ],
            'national-badminton-championship-unearths-teenage-prodigy' => [
                'জাতীয় ব্যাডমিন্টন চ্যাম্পিয়নশিপে ১৭ বছরের কিশোর শাটলারের চমকপ্রদ শিরোপা জয়',
                'চমৎকার ড্রপ শট ও বিদ্যুৎগতির স্ম্যাশে অভিজ্ঞ খেলোয়াড়দের হারিয়ে চ্যাম্পিয়ন হলেন দিনাজপুরের তরুণ।',
                [
                    'চট্টগ্রামের ইনডোর স্টেডিয়ামে অনুষ্ঠিত জাতীয় ব্যাডমিন্টন ফাইনালে শ্বাসরুদ্ধকর তিন সেটের লড়াইয়ে জয় পান এই তরুণ।',
                    'তার দুর্দান্ত রিফ্লেক্স ও ক্ষিপ্রগতির কোর্ট কভারেজ গ্যালারির দর্শকদের মুগ্ধ করে।',
                    'ব্যাডমিন্টন ফেডারেশন তাকে আন্তর্জাতিক প্রতিযোগিতার জন্য প্রস্তুত করতে বিশেষ বিদেশি প্রশিক্ষণের ব্যবস্থা করছে।',
                ],
            ],

            // Sports: International
            'youth-archery-contingent-claims-gold-at-asiad-qualifiers' => [
                'এশিয়ান যুব আর্চারিতে বাংলাদেশের সোনা জয়',
                'রিকার্ভ ও কম্পাউন্ড ইভেন্টে অসাধারণ লক্ষ্যভেদে জোড়া স্বর্ণপদক অর্জন।',
                [
                    'আন্তর্জাতিক আসরে চোখধাঁধানো নৈপুণ্য প্রদর্শন করে রিকার্ভ মিশ্র ও একক ইভেন্টে জোড়া সোনা জিতেছে বাংলাদেশের তরুণ আর্চাররা।',
                    'কোচেরা জানিয়েছেন, ক্রীড়া মনস্তত্ত্ব ও আধুনিক সিমুলেটরে অনুশীলনের ফলে খেলোয়াড়দের আত্মবিশ্বাস বহুগুণ বৃদ্ধি পেয়েছে।',
                    'জাতীয় আর্চারি ফেডারেশন জানিয়েছে, পদকজয়ী আর্চারদের বিশ্ব যুব চ্যাম্পিয়নশিপের জন্য বিশেষ নিবিড় ক্যাম্পে রাখা হবে।',
                ],
            ],
            'bangladesh-shooting-federation-secures-olympic-qualification-berth' => [
                '১০ মিটার এয়ার রাইফেলে সরাসরি অলিম্পিক কোটা অর্জন করলেন জাতীয় শুটার',
                'এশিয়ান শুটিং চ্যাম্পিয়নশিপের ফাইনালে নিখুঁত নিশানায় রৌপ্যপদক এবং অলিম্পিকের সরাসরি টিকিট লাভ।',
                [
                    'আন্তর্জাতিক মঞ্চে দুর্দান্ত একাগ্রতা প্রদর্শন করে সরাসরি অলিম্পিকে অংশ নেওয়ার গৌরব অর্জন করেছেন দেশের শীর্ষ শুটার।',
                    'চরম উত্তেজনার এলিমিনেশন রাউন্ডে তিনি ধারাবাহিকভাবে নিখুঁত স্কোর গড়ে আন্তর্জাতিক বিচারকদের প্রশংসা পান।',
                    'বাংলাদেশ অলিম্পিক অ্যাসোসিয়েশন শুটারের প্রস্তুতিতে সার্বিক কারিগরি ও উন্নত প্রশিক্ষণের নিশ্চয়তা দিয়েছে।',
                ],
            ],

            // Entertainment: Television
            'new-period-drama-series-depicts-1952-language-movement' => [
                'বায়ান্নর ভাষা আন্দোলনের আত্মত্যাগ নিয়ে তৈরি নতুন ধারাবাহিক নাটক',
                'তৎকালীন ছাত্রসমাজের সংগ্রামী ইতিহাস ও সাংস্কৃতিক জাগরণ তুলে ধরা হয়েছে পর্দায়।',
                [
                    '১৯৫২ সালের মহান ভাষা আন্দোলনের ঐতিহাসিক প্রেক্ষাপটে নির্মিত নতুন একটি ধারাবাহিক নাটক দর্শকের প্রশংসা কুড়াচ্ছে।',
                    'ছাত্রদের রক্তক্ষয়ী সংগ্রাম, সাহিত্যিকদের গোপন প্রকাশনা ও তৎকালীন সমাজের আবেগময় চিত্র চমৎকারভাবে ফুটিয়ে তোলা হয়েছে।',
                    'শিক্ষাবিদ ও সংস্কৃতিকর্মীরা তরুণ প্রজন্মকে সঠিক ইতিহাস জানানোর এই উদ্যোগের প্রশংসা করেছেন।',
                ],
            ],
            'children-science-magazine-show-becomes-weekend-television-hit' => [
                'কিশোরদের জন্য নির্মিত বিজ্ঞানভিত্তিক ম্যাগাজিন অনুষ্ঠান পরিবারজুড়ে জনপ্রিয়',
                'মজার মজার বৈজ্ঞানিক পরীক্ষা ও রোবট তৈরির গল্প নিয়ে তৈরি পর্বগুলো শিশুদের ভাবনার জগৎ প্রসারিত করছে।',
                [
                    'বাংলাদেশ বেতার ও টেলিভিশনের যৌথ প্রযোজনায় প্রচারিত বিজ্ঞান ম্যাগাজিন অনুষ্ঠানটি দর্শকনন্দিত হয়েছে।',
                    'মহাকাশের রহস্য, প্রকৃতির বৈচিত্র্য ও সহজ কোডিং নিয়ে তৈরি বিষয়গুলো শিশুদের সহজে শিখতে সাহায্য করছে।',
                    'অভিভাবকেরা জানিয়েছেন, সাপ্তাহিক ছুটির দিনে এই অনুষ্ঠানটি শিশুদের জন্য এক অনন্য শিক্ষণীয় বিনোদন।',
                ],
            ],

            // Entertainment: OTT
            'bengali-cyber-thriller-series-trends-globally-on-streaming' => [
                'আন্তর্জাতিক ওটিটি প্ল্যাটফর্মে শীর্ষ ট্রেন্ডিংয়ে ঢাকার সাইবার থ্রিলার সিরিজ',
                'টানটান চিত্রনাট্য, আধুনিক সিনেমাটোগ্রাফি ও অনবদ্য অভিনয়ে বিদেশি দর্শকের নজর কাড়ল দেশীয় ওয়েব সিরিজ।',
                [
                    'অনলাইনের অন্ধকার জগতের অপরাধ ও গোয়েন্দা অভিযান নিয়ে নির্মিত নতুন একটি থ্রিলার সিরিজ বিশ্বজুড়ে সাড়া ফেলেছে।',
                    'সিরিজের আবহসংগীত, আন্তর্জাতিক মানের কালার গ্রেডিং ও অভিনয়শিল্পীদের প্রাণবন্ত অভিনয় সমালোচকদের মন জয় করেছে।',
                    'নির্মাতা জানিয়েছেন, আন্তর্জাতিক সাফল্যের পর দ্বিতীয় সিজনের শুটিংয়ের প্রস্তুতি পুরোদমে শুরু হয়েছে।',
                ],
            ],
            'original-documentary-anthology-explores-historic-river-routes' => [
                'বাংলার ঐতিহ্যবাহী হারিয়ে যাওয়া কাঠের নৌকা নিয়ে ওটিটিতে প্রামাণ্যচিত্র সিরিজ',
                'ফোর-কে রেজল্যুশনে নদীপথের বজরা, পানসী ও ঘাসি নৌকার নির্মাণশৈলী ও মাঝিদের গান তুলে ধরা হয়েছে।',
                [
                    'নদীমাতৃক বাংলার প্রাচীন নৌকা কারিগরদের জীবন ও লোকসংগীত নিয়ে তৈরি বিশেষ তথ্যচিত্র সিরিজ মুক্তি পেয়েছে।',
                    'মাসব্যাপী গভীর নদীপথে অবস্থান করে ক্যামেরা টিম কাঠের নৌকার প্রতিটি নান্দনিক দিক নিখুঁতভাবে ধারণ করেছে।',
                    'সংস্কৃতিপ্রেমীরা বলেছেন, ডিজিটাল পর্দায় আমাদের লোকজ সংস্কৃতি সংরক্ষণের এই প্রয়াস সত্যিই প্রশংসনীয়।',
                ],
            ],

            // Entertainment: Films
            'indie-film-on-sundarbans-wins-international-festival-acclaim' => [
                'সুন্দরবনের জীবনগাথা নিয়ে নির্মিত চলচ্চিত্র আন্তর্জাতিক উৎসবে পুরস্কৃত',
                'মৌয়াল ও জেলেদের সংগ্রাম এবং বনের অপরূপ প্রকৃতির দৃশ্য আন্তর্জাতিক মহলে প্রশংসিত।',
                [
                    'সুন্দরবনের বাদাবন ও জলপথের পটভূমিতে নির্মিত একটি দেশীয় চলচ্চিত্র নামকরা আন্তর্জাতিক চলচ্চিত্র উৎসবে সেরা ছবির পুরস্কার জিতেছে।',
                    'স্থানীয় বনজীবীদের অভিনয়, প্রাকৃতিক রূপ ও নিখুঁত সাউন্ড ডিজাইনের ভূয়সী প্রশংসা করেছেন আন্তর্জাতিক সমালোচকরা।',
                    'চলচ্চিত্র নির্মাতা দল জানিয়েছে, শিগগিরই দেশের বিভিন্ন বিশ্ববিদ্যালয় ও প্রেক্ষাগৃহে সিনেমাটি প্রদর্শিত হবে।',
                ],
            ],
            'restored-classic-cinematic-masterpieces-screened-at-national-film-archive' => [
                'ফিল্ম আর্কাইভে ফোর-কে রিমাস্টার সংস্করণে প্রদর্শিত হলো সোনালী যুগের ধ্রুপদি চলচ্চিত্র',
                'ডিজিটাল কালার কারেকশন ও উন্নত সাউন্ডে আবার রুপালি পর্দায় ফিরে এলো ষাট ও সত্তরের দশকের কালজয়ী সিনেমা।',
                [
                    'বাংলাদেশ ফিল্ম আর্কাইভ তাদের সংগ্রহে থাকা ঐতিহাসিক সেলুলয়েড প্রিন্টগুলো আধুনিক প্রযুক্তিতে সংরক্ষণ করেছে।',
                    'চলচ্চিত্রপ্রেমী ও শিক্ষার্থীরা মূল পরিচালকদের অসাধারণ ফ্রেম ও কালজয়ী সংলাপ নতুনভাবে উপভোগ করেন।',
                    'আর্কাইভ কর্তৃপক্ষ জানিয়েছে, ভবিষ্যৎ প্রজন্মের জন্য আরও শত শত মূল্যবান চলচ্চিত্র এভাবে ডিজিটাল রূপান্তর করা হবে।',
                ],
            ],

            // Entertainment: Music
            'betar-golden-era-musical-archive-digitized-for-streaming' => [
                'বেতারের সোনালী যুগের সংগীত সম্ভার সংরক্ষিত হচ্ছে ডিজিটাল স্ট্রিমিংয়ে',
                'ষাটের ও সত্তরের দশকের কিংবদন্তি শিল্পীদের মাস্টার টেপ আধুনিক প্রযুক্তিতে ডিজিটালাইজড।',
                [
                    'বাংলাদেশ বেতার তাদের আর্কাইভে সংরক্ষিত হাজারো ঐতিহাসিক গান ও বেতার নাটকের মাস্টার টেপ আধুনিক প্রযুক্তিতে রিমাস্টারিং সম্পন্ন করেছে।',
                    'শ্রোতারা এখন যেকোনো ডিজিটাল মাধ্যমে কিংবদন্তি শিল্পীদের কালজয়ী কণ্ঠ ও শাস্ত্রীয় সংগীত শুনতে পারবেন।',
                    'গবেষকরা বলছেন, এই উদ্যোগ ভবিষ্যৎ প্রজন্মের জন্য আমাদের সমৃদ্ধ সাংস্কৃতিক ঐতিহ্যকে সুরক্ষিত রাখবে।',
                ],
            ],
            'fusion-orchestra-combines-sitar-and-cello-in-enchanting-concert' => [
                'সেতার ও ওয়েস্টার্ন অর্কেস্ট্রার মেলবন্ধনে মুগ্ধ হলেন সংগীতপ্রেমীরা',
                'সন্ধ্যা রাগের সঙ্গে বেহালার সুরের অপূর্ব সংমিশ্রণে অনুষ্ঠিত হলো জাদুকরী লাইভ কনসার্ট।',
                [
                    'রাজধানীর মিলনায়তনে দেশি ও বিদেশি শাস্ত্রীয় যন্ত্রশিল্পীদের যুগলবন্দি পরিবেশনা দর্শকদের বিমোহিত করে।',
                    'রাগ ইমন ও পাশ্চাত্য সিম্ফনির এক অনন্য সুরের ধারায় গোটা সন্ধ্যা পরিণত হয়েছিল এক সুরেলা উৎসবে।',
                    'আয়োজকেরা জানিয়েছেন, এই লাইভ কনসার্টের অডিও ট্র্যাক শিগগিরই ডিজিটাল প্ল্যাটফর্মে প্রকাশ করা হবে।',
                ],
            ],
            'nazrul-geeti-modern-acoustic-renditions-draw-millions-of-streams' => [
                'অ্যাকোস্টিক সুরে তরুণদের কণ্ঠে নতুন রূপ পেল বিদ্রোহী কবির কালজয়ী গান',
                'বিশুদ্ধ সুর অক্ষুণ্ণ রেখে গিটার ও এসরাজের সংমিশ্রণে নজরুলের গান শুনছেন কোটি শ্রোতা।',
                [
                    'জাতীয় কবি কাজী নজরুল ইসলামের কালজয়ী গানগুলোর আধুনিক অ্যাকোস্টিক অ্যালবাম প্রকাশিত হয়ে ডিজিটাল মাধ্যমে সাড়া ফেলেছে।',
                    'তরুণ প্রজন্মের সংগীতশিল্পীরা মূল স্বরলিপি অনুসরণ করে আধুনিক সাউন্ড ডিজাইনে গানগুলো পরিবেশন করেছেন।',
                    'সংগীত গবেষকেরা জানিয়েছেন, নতুন শৈলীতে পরিবেশন করায় তরুণদের মাঝে নজরুল সংগীতের আবেদন বহুগুণ বেড়েছে।',
                ],
            ],

            // Entertainment: Drama
            'dhaka-theatre-festival-showcases-young-playwrights' => [
                'ঢাকা নাট্যোৎসবে তরুণ নাট্যকারদের মঞ্চনাটক উপভোগ করছেন দর্শক',
                'সমসাময়িক সমাজ ও তরুণ প্রজন্মের আত্মানুসন্ধান নিয়ে মঞ্চস্থ হচ্ছে নতুন নতুন নাটক।',
                [
                    'রাজধানীতে আয়োজিত জাতীয় নাট্যোৎসবে নবীন নাট্যকার ও নির্দেশকদের মৌলিক নাটক দেখতে মিলনায়তনগুলোতে দর্শকের ভিড় জমছে।',
                    'নব্য ধারার মঞ্চসজ্জা ও দেশীয় বাদ্যযন্ত্রের সংমিশ্রণে প্রতিটি নাটক দর্শকের ভূয়সী প্রশংসা অর্জন করছে।',
                    'উৎসব কর্তৃপক্ষ সেরা নাটকগুলোকে দেশের বিভিন্ন জেলা শহরে মঞ্চায়নের জন্য বিশেষ ফেলোশিপ ঘোষণা করেছে।',
                ],
            ],
            'radio-drama-festival-features-contemporary-social-comedies' => [
                'বেতার নাট্যোৎসবে প্রচারিত হচ্ছে নতুন সামাজিক রম্য ও রোমাঞ্চকর নাটক',
                'থ্রিডি বাইনরাল সাউন্ড ইফেক্ট ও প্রখ্যাত কণ্ঠশিল্পীদের অভিনয়ে ঘরে ঘরে উপভোগ করছেন লাখো শ্রোতা।',
                [
                    'বাংলাদেশ বেতারের উদ্যোগে আয়োজিত বার্ষিক বেতার নাট্যোৎসবে দশটি নতুন মৌলিক নাটক সম্প্রচারিত হচ্ছে।',
                    'স্টুডিওর শব্দশিল্পীদের নিখুঁত ফলি সাউন্ড নাটকের প্রতিটি মুহূর্তকে জীবন্ত ও বিশ্বাসযোগ্য করে তুলেছে।',
                    'বিশ্ববিদ্যালয়ের হল ও চায়ের দোকানে দলবেঁধে রেডিও শোনার পুরোনো ঐতিহ্য আবার ফিরে আসছে।',
                ],
            ],

            // Entertainment: Interviews
            'master-foley-artist-shares-fifty-years-of-acoustic-sound-magic' => [
                'বেতারের প্রবীণ শব্দশিল্পীর মুখোমুখি: ৫০ বছরের জাদুকরী শব্দ তৈরির অভিজ্ঞতা',
                'টিনের পাত, নারকেলের মালা ও পাথরের গুঁড়ো দিয়ে কীভাবে তৈরি হয় নাটকের ঝড়-বৃষ্টি ও ট্রেনের শব্দ।',
                [
                    'বাংলাদেশ বেতারে দীর্ঘ পাঁচ দশক ধরে কাজ করা প্রবীণ শব্দশিল্পী এক একান্ত সাক্ষাৎকারে তার কাজের নানা গল্প শোনান।',
                    'মুক্তিযুদ্ধ চলাকালে স্বাধীন বাংলা বেতারের রোমাঞ্চকর স্মৃতি এবং শব্দ প্রকৌশলের খুঁটিনাটি তিনি তুলে ধরেন।',
                    'সাক্ষাৎকারটির সাথে তার তৈরি বিভিন্ন অসাধারণ সাউন্ড এফেক্টের অডিও ক্লিপ শ্রোতাদের জন্য উন্মুক্ত করা হয়েছে।',
                ],
            ],

            // Jobs: Government
            'bcs-recruitment-circular-announces-new-technical-cadres' => [
                'বিসিএসে নতুন কারিগরি ক্যাডার যুক্ত করে নিয়োগ বিজ্ঞপ্তি প্রকাশ',
                'ডেটা অ্যানালিটিক্স, পরিবেশ বিজ্ঞান ও সাইবার নিরাপত্তায় দক্ষ তরুণদের প্রশাসনে সুযোগ।',
                [
                    'পাবলিক সার্ভিস কমিশন তাদের নতুন নিয়োগ বিজ্ঞপ্তিতে প্রযুক্তিনির্ভর বেশ কয়েকটি নতুন কারিগরি ক্যাডার পদ অন্তর্ভুক্ত করেছে।',
                    'তথ্যপ্রযুক্তি, পরিবেশ বিজ্ঞান ও পরিসংখ্যানের স্নাতকেরা এসব পদে সরাসরি আবেদনের সুযোগ পাবেন।',
                    'কমিশন নিশ্চিত করেছে, সম্পূর্ণ মেধা ও স্বচ্ছতার ভিত্তিতে প্রিলিমিনারি ও মৌখিক পরীক্ষা অনুষ্ঠিত হবে।',
                ],
            ],
            'primary-teacher-recruitment-results-published-nationwide' => [
                'প্রাথমিক সহকারী শিক্ষক নিয়োগের চূড়ান্ত ফলাফল প্রকাশ',
                'মেধা ও যোগ্যতার ভিত্তিতে সারা দেশের প্রাথমিক বিদ্যালয়ে নিয়োগ পাচ্ছেন হাজারো তরুণ শিক্ষক।',
                [
                    'প্রাথমিক শিক্ষা অধিদপ্তর সহকারী শিক্ষক নিয়োগের চূড়ান্ত মেধা তালিকা অনলাইনে প্রকাশ করেছে।',
                    'বায়োমেট্রিক ও স্বয়ংক্রিয় নম্বর গণনার মাধ্যমে সম্পূর্ণ স্বচ্ছতার সাথে ফলাফল প্রস্তুত করা হয়েছে।',
                    'নতুন শিক্ষকদের জন্য আধুনিক শিশুতোষ পাঠদান ও ডিজিটাল শ্রেণিকক্ষ পরিচালনার ওপর বিশেষ প্রশিক্ষণ দেওয়া হবে।',
                ],
            ],
            'judicial-service-commission-issues-assistant-judge-intake-notice' => [
                'সহকারী জজ নিয়োগে জুডিশিয়াল সার্ভিস কমিশনের পরীক্ষার বিজ্ঞপ্তি জারি',
                'আদালতগুলোতে মামলার দ্রুত নিষ্পত্তি নিশ্চিতে আইন স্নাতকদের জন্য প্রতিযোগিতামূলক পরীক্ষার আহ্বান।',
                [
                    'বাংলাদেশ জুডিসিয়াল সার্ভিস কমিশন সহকারী জজ পদে নতুন ব্যাচ নিয়োগের বিস্তারিত বিজ্ঞপ্তি প্রকাশ করেছে।',
                    'আইন পেশায় আগ্রহী তরুণদের মেধা যাচাইয়ে প্রিলিমিনারি, লিখিত ও মনস্তাত্ত্বিক মৌখিক পরীক্ষা গ্রহণ করা হবে।',
                    'কমিশন জানিয়েছে, দ্রুত বিচার সেবা সাধারণ মানুষের কাছে পৌঁছে দিতে এই নিয়োগ কার্যকর ভূমিকা রাখবে।',
                ],
            ],

            // Jobs: Private
            'it-freelancing-skill-hubs-launched-in-twenty-districts' => [
                '২০ জেলায় চালু হলো আইটি ফ্রিল্যান্সিং ও আধুনিক স্কিল হাব',
                'বিশ্ববাজারে রিমোট কাজের সুযোগ তৈরি করতে গ্রামীণ তরুণদের দেয়া হচ্ছে বিনা মূল্যে প্রশিক্ষণ।',
                [
                    'তথ্যপ্রযুক্তি বিভাগ সারা দেশের ২০টি জেলায় আধুনিক ফ্রিল্যান্সিং ও সফটওয়্যার প্রশিক্ষণ হাব চালু করেছে।',
                    'প্রশিক্ষণার্থীরা আন্তর্জাতিক মার্কেটপ্লেসে কাজ পাওয়ার জন্য প্রয়োজনীয় কোডিং, গ্রাফিক্স ও ডিজিটাল মার্কেটিং শিখছেন।',
                    'প্রথম ব্যাচের সফল প্রশিক্ষণার্থীরা ইতিমধ্যে ব্যাংকিং চ্যানেলে সম্মানজনক বৈদেশিক মুদ্রা আয় করতে শুরু করেছেন।',
                ],
            ],
            'polytechnic-graduates-gain-fast-track-industrial-apprenticeships' => [
                'পলিটেকনিক ডিপ্লোমাধারীদের জন্য শিল্পপ্রতিষ্ঠানে পেইড ইন্টার্নশিপ চুক্তি',
                'অটোমোবাইল, ইলেকট্রিক্যাল ও টেক্সটাইল কারখানায় সরাসরি কাজ শেখার ও স্থায়ী চাকরির সুযোগ।',
                [
                    'কারিগরি শিক্ষা বোর্ড ও শীর্ষস্থানীয় শিল্পগ্রুপগুলোর মধ্যে যৌথ ইন্টার্নশিপ চুক্তি স্বাক্ষরিত হয়েছে।',
                    'ছয় মাসের এই বাস্তবমুখী প্রশিক্ষণে শিক্ষার্থীরা ফ্যাক্টরি ফ্লোরে অত্যাধুনিক যন্ত্রপাতির ব্যবহার শিখছেন।',
                    'শিল্পমালিকেরা জানিয়েছেন, দক্ষ ডিপ্লোমা প্রকৌশলীদের প্রশিক্ষণ শেষে স্থায়ী পদে নিয়োগ প্রদান করা হবে।',
                ],
            ],
            'fintech-and-e-commerce-sectors-announce-thousands-of-tech-openings' => [
                'ফিনটেক ও ডিজিটাল কমার্স খাতে সফটওয়্যার ইঞ্জিনিয়ারদের ব্যাপক নিয়োগ',
                'ক্লাউড আর্কিটেকচার, মোবাইল অ্যাপ ও ডেটা সায়েন্সে দক্ষ কর্মীদের আকর্ষণীয় বেতনে কাজের সুযোগ।',
                [
                    'ডিজিটাল পেমেন্ট ও ই-কমার্স কোম্পানিগুলো দেশের প্রযুক্তি প্রতিভাদের জন্য বড় নিয়োগ বিজ্ঞপ্তি প্রকাশ করেছে।',
                    'কর্মীদের জন্য আকর্ষণীয় আর্থিক সুবিধা, হাইব্রিড কাজের সুযোগ ও আন্তর্জাতিক মেন্টরশিপের ব্যবস্থা রাখা হয়েছে।',
                    'খাত সংশ্লিষ্টরা বলছেন, স্থানীয় প্রযুক্তিকর্মীদের ওপর আন্তর্জাতিক আস্থা বৃদ্ধি পাওয়ায় কর্মসংস্থান বাড়ছে।',
                ],
            ],

            // Jobs: Career
            'career-counseling-bootcamps-guide-graduates-in-emerging-ai-fields' => [
                'বিশ্ববিদ্যালয় স্নাতকদের জন্য এআই ও আধুনিক প্রযুক্তির ক্যারিয়ার বুটক্যাম্প',
                'গিটহাব পোর্টফোলিও তৈরি, টেকনিক্যাল ইন্টারভিউ প্রস্তুতি ও বাস্তব প্রকল্পের ওপর দিকনির্দেশনা।',
                [
                    'তথ্যপ্রযুক্তি খাতের অভিজ্ঞ প্রকৌশলীদের পরিচালনায় তরুণদের জন্য বিশেষ ক্যারিয়ার কর্মশালা অনুষ্ঠিত হয়েছে।',
                    'অংশগ্রহণকারীরা আধুনিক কৃত্রিম বুদ্ধিমত্তা ও সফটওয়্যার ডেভেলপমেন্টের আন্তর্জাতিক ট্রেন্ড সম্পর্কে বাস্তব ধারণা পান।',
                    'মেন্টররা পরামর্শ দিয়েছেন, প্রতিনিয়ত নতুন প্রযুক্তি শিখে নিজেকে আপডেটেড রাখাই ক্যারিয়ারের সফলতার মূল চাবিকাঠি।',
                ],
            ],
            'soft-skills-and-english-communication-workshops-expand-in-colleges' => [
                'কলেজ পর্যায়ের শিক্ষার্থীদের জন্য করপোরেট যোগাযোগ ও লিডারশিপ কর্মশালা',
                'উপস্থাপনা, ব্যবসায়িক ইংরেজি ও দলগত কাজের দক্ষতা বৃদ্ধিতে জেলাভিত্তিক বিশেষ প্রশিক্ষণ সেশন।',
                [
                    'জেলা পর্যায়ের সরকারি কলেজগুলোতে শিক্ষার্থীদের চাকরির পরীক্ষার জন্য প্রস্তুত করতে সফট স্কিল কোর্স চালু হয়েছে।',
                    'সরাসরি মক ইন্টারভিউ ও গ্রুপ ডিসকাশনে অংশ নিয়ে শিক্ষার্থীরা তাদের জড়তা কাটিয়ে উঠছেন।',
                    'অংশগ্রহণকারী তরুণরা জানিয়েছেন, এই প্রশিক্ষণ তাদের চাকরি পাওয়ার আত্মবিশ্বাস বহুগুণ বাড়িয়ে দিয়েছে।',
                ],
            ],

            // Jobs: Results
            'bankers-selection-committee-publishes-senior-officer-merit-list' => [
                'রাষ্ট্রায়ত্ত ব্যাংকগুলোতে সিনিয়র অফিসার নিয়োগের চূড়ান্ত ফল প্রকাশ',
                'বাংলাদেশ ব্যাংকের ব্যাংকার্স সিলেকশন কমিটির মাধ্যমে মনোনীত হলেন তিন সহস্রাধিক যোগ্য প্রার্থী।',
                [
                    'সম্মিলিত ব্যাংক নিয়োগ পরীক্ষার চূড়ান্ত ফলাফল আজ বাংলাদেশ ব্যাংকের ওয়েবসাইটে প্রকাশিত হয়েছে।',
                    'উত্তীর্ণ প্রার্থীরা বাংলাদেশ ইনস্টিটিউট অব ব্যাংক ম্যানেজমেন্টে নিবিড় বুনিয়াদি প্রশিক্ষণ গ্রহণ করবেন।',
                    'স্বচ্ছ ও দ্রুত ফলাফল প্রস্তুতের প্রক্রিয়াটি চাকরিপ্রার্থীদের মাঝে দারুণ প্রশংসা কুড়িয়েছে।',
                ],
            ],
            'nursing-and-midwifery-recruitment-examination-results-declared' => [
                'নার্সিং ও মিডওয়াইফারি অধিদপ্তরের স্টাফ নার্স নিয়োগের ফল প্রকাশ',
                'উপজেলা স্বাস্থ্য কমপ্লেক্স ও বিশেষায়িত সরকারি হাসপাতালে পদায়ন পাচ্ছেন হাজারো দক্ষ নার্স।',
                [
                    'স্বাস্থ্য মন্ত্রণালয়ের নার্সিং নিয়োগ বোর্ডের মেধা তালিকা অনুযায়ী উত্তীর্ণদের নাম ঘোষণা করা হয়েছে।',
                    'নতুন নার্সদের যোগদানে গ্রামীণ এলাকার হাসপাতালগুলোতে প্রসূতি ও জরুরি স্বাস্থ্যসেবা আরও গতিশীল হবে।',
                    'নিয়োগপ্রাপ্ত স্বাস্থ্যকর্মীরা প্রত্যন্ত অঞ্চলের মানুষের আন্তরিক সেবা প্রদানের অঙ্গীকার ব্যক্ত করেছেন।',
                ],
            ],

            // Lifestyle: Health
            'community-health-clinics-introduce-telemedicine-counseling' => [
                'কমিউনিটি ক্লিনিকে বিশেষজ্ঞ চিকিৎসকের টেলিমেডিসিন ও মানসিক স্বাস্থ্য পরামর্শ',
                'ভিডিও কলের মাধ্যমে প্রত্যন্ত অঞ্চলের রোগীরা সরাসরি পাচ্ছেন রাজধানী ও বিভাগীয় শহরের চিকিৎসকের সেবা।',
                [
                    'উত্তরের বেশ কয়েকটি উপজেলার গ্রামীণ কমিউনিটি ক্লিনিকে নিয়মিত টেলিমেডিসিন সেবা চালু করা হয়েছে।',
                    'মা ও শিশুরা বিনা মূল্যে অভিজ্ঞ বিশেষজ্ঞ ডাক্তারের পরামর্শ ও প্রয়োজনীয় ব্যবস্থাপত্র গ্রহণ করছেন।',
                    'স্বাস্থ্যকর্মীরা জানিয়েছেন, মানসিক স্বাস্থ্য বিষয়ে নিয়মিত কাউন্সেলিং পেয়ে গ্রামীণ নারীদের জীবনমান উন্নত হচ্ছে।',
                ],
            ],
            'nationwide-nutrition-campaign-promotes-indigenous-superfoods' => [
                'দেশীয় ছোট মাছ ও শাকসবজির পুষ্টিগুণ নিয়ে জাতীয় সচেতনতামূলক ক্যাম্পেইন',
                'মোলা, ঢেলা মাছ ও লাল চালের ভাত শিশুদের রোগ প্রতিরোধ ক্ষমতা বাড়াতে দারুণ ভূমিকা রাখছে।',
                [
                    'জনস্বাস্থ্য পুষ্টি প্রতিষ্ঠান দেশের ঐতিহ্যবাহী সহজলভ্য খাবারের পুষ্টিগুণ তুলে ধরে প্রচারণা শুরু করেছে।',
                    'ভিটামিন ও খনিজ উপাদানে সমৃদ্ধ দেশীয় শাকসবজি শিশুদের শারীরিক ও মানসিক বিকাশে অত্যন্ত কার্যকর।',
                    'কমিউনিটি ক্লিনিকগুলোতে মায়েদের স্বল্প খরচে পুষ্টিকর খাবার তৈরির সহজ রেসিপি শেখানো হচ্ছে।',
                ],
            ],
            'mental-wellbeing-hotline-provides-round-the-clock-support-for-youth' => [
                'মানসিক স্বাস্থ্য সহায়তায় ২৪ ঘণ্টা সার্বক্ষণিক টোল-ফ্রি হেল্পলাইন সেবা',
                'পরীক্ষার চাপ, হতাশা ও মানসিক উদ্বেগ দূর করতে পেশাদার সাইকোলজিস্টদের সাথে কথা বলার সুযোগ।',
                [
                    'স্বাস্থ্য অধিদপ্তর পরিচালিত মানসিক স্বাস্থ্য হেল্পলাইনের ক্ষমতা বৃদ্ধি করা হয়েছে।',
                    'নাম প্রকাশ না করেই যেকোনো তরুণ শিক্ষার্থী মানসিক চাপ নিয়ে চিকিৎসকের কাছ থেকে সরাসরি পরামর্শ পাচ্ছেন।',
                    'মানসিক স্বাস্থ্য বিশেষজ্ঞরা এই সেবাকে মানসিক সমস্যা দূরীকরণে একটি সময়োপযোগী পদক্ষেপ বলে অভিহিত করেছেন।',
                ],
            ],

            // Lifestyle: Education
            'student-robotics-team-heads-to-regional-final' => [
                'রোবটিক্স প্রতিযোগিতার আন্তর্জাতিক ফাইনালে বাংলাদেশের তরুণ শিক্ষার্থী দল',
                'স্থানীয় সরঞ্জাম দিয়ে তৈরি উদ্ধারকারী রোভার আন্তর্জাতিক বিচারকদের মন জয় করে চূড়ান্ত পর্বে।',
                [
                    'বিশ্ববিদ্যালয়ের প্রকৌশল শিক্ষার্থীদের তৈরি স্বল্প ব্যয়ের স্বয়ংক্রিয় রোভার এশীয় প্রতিযোগিতার ফাইনালে স্থান করে নিয়েছে।',
                    'বিপদজনক সংকীর্ণ স্থান পরিদর্শন এবং পরিবেশের তথ্য সংগ্রহে এই রোবটটি কার্যকরভাবে কাজ করতে সক্ষম।',
                    'শিক্ষার্থী দলটি আশা প্রকাশ করেছে, এই সাফল্য দেশের অন্যান্য শিক্ষার্থীদের বিজ্ঞান চর্চায় উদ্বুদ্ধ করবে।',
                ],
            ],
            'interactive-smart-classrooms-transform-rural-high-schools' => [
                'এক হাজার গ্রামীণ মাধ্যমিক বিদ্যালয়ে চালু হলো আধুনিক ডিজিটাল স্মার্ট ক্লাসরুম',
                'মাল্টিমিডিয়া প্রজেক্টর ও অ্যানিমেটেড বিজ্ঞানের পাঠে গ্রামীণ শিক্ষার্থীদের পড়াশোনায় দারুণ আগ্রহ।',
                [
                    'মাধ্যমিক শিক্ষা উন্নয়ন প্রকল্পের আওতায় সারা দেশের প্রত্যন্ত বিদ্যালয়ের শ্রেণিকক্ষগুলো আধুনিকায়ন করা হয়েছে।',
                    'শিক্ষকেরা ভার্চুয়াল ল্যাব ও থ্রিডি অ্যানিমেশনের সাহায্যে গণিত ও বিজ্ঞানের কঠিন বিষয়গুলো সহজে বোঝাচ্ছেন।',
                    'প্রধান শিক্ষকেরা জানিয়েছেন, স্মার্ট ক্লাস চালুর পর শিক্ষার্থীদের ক্লাসে উপস্থিতির হার নাটকীয়ভাবে বেড়েছে।',
                ],
            ],

            // Lifestyle: Travel
            'eco-tourism-trails-gain-popularity-in-bandarban-hills' => [
                'বান্দরবানের পাহাড়ে স্থানীয় জনগোষ্ঠীর পরিচালনায় জমে উঠেছে ইকো-ট্যুরিজম ট্রেইল',
                'প্লাস্টিকমুক্ত পাহাড়ি পথ, পাহাড়ি হোমস্টে ও স্থানীয় ঐতিহ্যবাহী খাবারের স্বাদ নিচ্ছেন ভ্রমণপ্রেমীরা।',
                [
                    'পার্বত্য চট্টগ্রামের প্রাকৃতিক সৌন্দর্যকে অক্ষুণ্ণ রেখে পরিচালিত নতুন ট্রেকিং রুটগুলো পর্যটকদের আকৃষ্ট করছে।',
                    'স্থানীয় গাইডদের পরিচালনায় পরিচালিত এই ভ্রমণে পাহাড়ি সংস্কৃতি ও পরিবেশ রক্ষার নিয়ম কড়াকড়িভাবে মানা হচ্ছে।',
                    'হোমস্টে মালিকেরা জানিয়েছেন, এই পর্যটন তাদের গ্রামের স্কুল পরিচালনা ও বন রক্ষায় আয়ের নতুন উৎস তৈরি করেছে।',
                ],
            ],
            'heritage-walking-tours-unveil-architectural-wonders-of-old-dhaka' => [
                'পুরান ঢাকার প্রাচীন স্থাপত্য ও ঐতিহ্যবাহী খাবারের স্বাদ নিয়ে হেরিটেজ ওয়াক',
                'ইতিহাসপ্রেমীরা হেঁটে দেখছেন লালবাগ কেল্লা, তারা মসজিদ ও শতবর্ষী সওদাগর বাড়ির অপরূপ রূপ।',
                [
                    'ঐতিহাসিক স্থাপত্য সংরক্ষণবিদদের উদ্যোগে পুরান ঢাকার অলিগলিতে নিয়মিত হেরিটেজ ওয়াকিং ট্যুর পরিচালিত হচ্ছে।',
                    'পর্যটকেরা ঢাকার মুঘল ও ঔপনিবেশিক আমলের স্থাপত্যের গল্প শুনছেন এবং বাকরখানি ও সুস্বাদু চা উপভোগ করছেন।',
                    'আয়োজকেরা জানান, এই উদ্যোগ পুরান ঢাকার প্রাচীন ভবনগুলো সংরক্ষণে জনসচেতনতা তৈরি করছে।',
                ],
            ],
            'tanguar-haor-monsoon-houseboat-cruising-attracts-nature-lovers' => [
                'বর্ষার টাঙ্গুয়ার হাওরে কাঠের বজরা নৌকায় ভ্রমণপিপাসুদের অপার আনন্দ',
                'মেঘালয় পাহাড়ের পাদদেশে স্বচ্ছ নীল জলরাশির ওপর ভেসে চলা আধুনিক সুসজ্জিত হাউসবোট।',
                [
                    'সুনামগঞ্জের রামসার সাইট টাঙ্গুয়ার হাওর বর্ষার মৌসুমে দেশ-বিদেশের পর্যটকদের প্রধান আকর্ষণে পরিণত হয়েছে।',
                    'পরিবেশবান্ধব কাঠের হাউসবোটগুলোতে রয়েছে সৌরবিদ্যুৎ ও বর্জ্য ব্যবস্থাপনার আধুনিক সুযোগ-সুবিধা।',
                    'হাওরের তরতাজা মাছের রান্না এবং জ্যোৎস্না রাতে ছাদের ওপর বসে লোকগান শোনার অনুভূতি পর্যটকদের মুগ্ধ করে।',
                ],
            ],

            // Lifestyle: Food
            'revival-of-traditional-pitha-festivals-celebrates-winter-heritage' => [
                'শীতের আমেজে দেশজুড়ে জমে উঠেছে ঐতিহ্যবাহী পিঠা উৎসবের মেলা',
                'ভাপা, চিতই, পাটিসাপটা ও পুলি পিঠার ধোঁয়া ওঠা স্বাদে মেতে উঠেছে নাগরিক ও গ্রামীণ জীবন।',
                [
                    'শীতের শুরুতেই শহর ও গ্রামের খোলা প্রাঙ্গণগুলোতে শুরু হয়েছে ঐতিহ্যবাহী লোকজ পিঠা মেলা।',
                    'মাটির চুলায় টাটকা খেজুরের গুড়, নারকেল ও নতুন চালের গুঁড়ো দিয়ে তৈরি হচ্ছে সুস্বাদু সব পিঠা।',
                    'খাদ্য গবেষকেরা বলেছেন, বাঙালির চিরায়ত পিঠাপুলির উৎসব আমাদের পারিবারিক ও সামাজিক বন্ধন দৃঢ় করে।',
                ],
            ],
            'organic-kitchen-gardening-takes-root-in-urban-rooftops' => [
                'শহরের ছাদবাগানে ফলছে বিষমুক্ত তাজা শাকসবজি ও ফলমূল',
                'নগরবাসীর উদ্যোগে ছাদগুলো পরিণত হচ্ছে সবুজ উদ্যানে; মিলছে টাটকা টমেটো, মরিচ ও পুদিনা পাতা।',
                [
                    'ঢাকা ও চট্টগ্রামের আবাসিক ভবনের ছাদে ড্রিপ ইরিগেশন ও জৈব সার ব্যবহার করে গড়ে উঠছে আধুনিক ছাদবাগান।',
                    'নিজের হাতে ফলানো তাজা সবজি পরিবারের পুষ্টির চাহিদা মেটানোর পাশাপাশি ভবনের অভ্যন্তরীণ তাপমাত্রা শীতল রাখছে।',
                    'সিটি করপোরেশন ছাদবাগানীদের গৃহকর ছাড় ও বিনামূল্যে চারা বিতরণের মাধ্যমে উৎসাহিত করছে।',
                ],
            ],

            // Lifestyle: Fashion
            'traditional-handloom-weavers-bridge-heritage-and-modern-fashion' => [
                'জামদানি ও খাদি কাপড়ে তরুণ ডিজাইনারদের আধুনিক ফ্যাশন ফিউশন',
                'ঐতিহ্যবাহী তাঁতশিল্পীদের দক্ষ হাতের বুনন ও পরিবেশবান্ধব রঙে তৈরি পোশাক নজর কাড়ছে সবার।',
                [
                    'টাঙ্গাইল ও সোনারগাঁয়ের ঐতিহ্যবাহী জামদানি তাঁতিদের সঙ্গে ফ্যাশন ডিজাইনাররা যৌথভাবে নতুন পোশাক তৈরি করছেন।',
                    'প্রাকৃতিক সুতা ও উদ্ভিজ্জ রঙের ব্যবহার কাপড়ে এনে দিচ্ছে অনন্য কোমলতা ও দীর্ঘস্থায়িত্ব।',
                    'দেশ-বিদেশের ফ্যাশন প্রদর্শনীতে খাঁটি দেশীয় কাপড়ের এই ফিউশন দারুণ বাণিজ্যিক সাফল্য লাভ করছে।',
                ],
            ],
            'sustainable-jute-apparel-collection-makes-waves-at-fashion-week' => [
                'জাতীয় ফ্যাশন সপ্তাহে পাটের আধুনিক কাপড়ে তৈরি ব্লেজার ও পোশাকের চমক',
                'সোনালী আঁশের পরিশোধিত কাপড়ে তৈরি আরামদায়ক ও পরিবেশবান্ধব পোশাকের প্রশংসা আন্তর্জাতিক ক্রেতাদের।',
                [
                    'ঢাকা সাসটেইনেবল ফ্যাশন শোতে পাটের সুতা দিয়ে তৈরি আধুনিক ব্লেজার, গাউন ও ব্যাগ প্রদর্শিত হয়েছে।',
                    'সম্পূর্ণ বায়োডিগ্রেডেবল ও টেকসই হওয়ায় বিশ্বজুড়ে কৃত্রিম কাপড়ের বিকল্প হিসেবে এর গ্রহণযোগ্যতা বাড়ছে।',
                    'আন্তর্জাতিক ফ্যাশন ব্র্যান্ডগুলো এই দেশীয় ব্লেন্ডেড ফেব্রিক সংগ্রহের ব্যাপারে গভীর আগ্রহ দেখিয়েছে।',
                ],
            ],

            // Video: News Video
            'community-radio-expands-agriculture-bulletins' => [
                'কমিউনিটি রেডিওতে কৃষকদের জন্য বিশেষ ভিডিও বুলেটিন ও আবহাওয়া বার্তা',
                'ফসলের বাজারদর, রোগবালাই প্রতিকার ও আবহাওয়ার পূর্বাভাস সরাসরি জানাচ্ছেন কৃষি কর্মকর্তারা।',
                [
                    'আঞ্চলিক রেডিও কেন্দ্রগুলোতে কৃষকদের জন্য সময়োপযোগী বিশেষ কৃষি ভিডিও সংবাদ সম্প্রচার শুরু হয়েছে।',
                    'মাঠে কাজ করা কৃষকদের সুবিধাজনক সময়ে অনুষ্ঠানগুলো প্রচার ও ডিজিটাল মাধ্যমে শেয়ার করা হচ্ছে।',
                    'শ্রোতা ও দর্শকেরা মোবাইল ফোনে সরাসরি প্রশ্ন পাঠিয়ে পরবর্তী পর্বে বিশেষজ্ঞ সমাধান গ্রহণ করছেন।',
                ],
            ],
            'video-bulletin-metro-rail-terminal-interchange-in-full-motion' => [
                'ভিডিও প্রতিবেদন: আধুনিক মাল্টিমোডাল টার্মিনালে যেভাবে সহজ হচ্ছে ঢাকার যাতায়াত',
                'ড্রোন ফুটেজ ও যাত্রীদের বক্তব্যে ফুটে উঠেছে মেট্রো, আধুনিক বাস ও পথচারী প্লাজার চমৎকার সংযোগ।',
                [
                    'রাজধানীর ব্যস্ততম সমন্বিত পরিবহন টার্মিনালের সার্বিক কার্যক্রম নিয়ে প্রচারিত হয়েছে বিশেষ ভিডিও প্রতিবেদন।',
                    'মেট্রো থেকে নেমে কোনো যানজট ছাড়াই দুই মিনিটের মধ্যে সংযোগকারী বাসে ওঠার চমৎকার ব্যবস্থাপনা দেখানো হয়েছে।',
                    'পরিবহন বিশেষজ্ঞরা ভিডিওতে শহরের অন্যান্য প্রবেশদ্বারেও একই রকম টার্মিনাল নির্মাণের পরিকল্পনা তুলে ধরেন।',
                ],
            ],

            // Video: Interviews
            'video-conversation-with-pioneering-delta-hydrologist-on-river-resilience' => [
                'ভিডিও সাক্ষাৎকার: প্রখ্যাত পানিবিজ্ঞানীর সঙ্গে নদীভাঙন ও ডেল্টা সুরক্ষার ভবিষ্যৎ নিয়ে আলোচনা',
                'পদ্মা নদীর পাড়ে বসে জলবায়ু অভিযোজন, নদী ড্রেজিং ও পানির সুষম বণ্টন নিয়ে বিশ্লেষণ।',
                [
                    'নদীমাতৃক বাংলাদেশের পানির ব্যবস্থাপনা নিয়ে আন্তর্জাতিক খ্যাতিসম্পন্ন হাইড্রোলজিস্টের সাথে বিশেষ ভিডিও আলাপ।',
                    'কংক্রিটের বাঁধের চেয়ে প্রকৃতির সাথে সামঞ্জস্য রেখে পলিমাটি ব্যবস্থাপনার ওপর তিনি বিশেষ জোর দেন।',
                    'ভিডিওতে কম্পিউটার গ্রাফিক্সের মাধ্যমে জোয়ার-ভাটার সাহায্যে কীভাবে নতুন চর তৈরি করা যায় তা তুলে ধরা হয়েছে।',
                ],
            ],

            // Video: Explainers
            'explainer-how-the-bangabandhu-tunnel-changes-southern-logistics' => [
                'ভিডিও ব্যাখ্যা: বঙ্গবন্ধু টানেল কীভাবে দক্ষিণাঞ্চলের বাণিজ্যে বিপ্লব ঘটাচ্ছে',
                'দক্ষিণ এশিয়ার প্রথম আন্ডারওয়াটার এক্সপ্রেসওয়ে টানেলের নির্মাণকৌশল ও অর্থনৈতিক সুবিধার ভিডিও বিশ্লেষণ।',
                [
                    'কর্ণফুলী নদীর তলদেশের বিস্ময়কর টানেলের প্রকৌশল ও লজিস্টিক সুবিধা নিয়ে নির্মিত হয়েছে বিশেষ ব্যাখ্যামূলক ভিডিও।',
                    'গ্রাফিক্স অ্যানিমেশনে দেখা গেছে কীভাবে শহরের জট এড়িয়ে মাত্র কয়েক মিনিটে গভীর সমুদ্রবন্দরের মালামাল খালাস হচ্ছে।',
                    'অর্থনীতিবিদরা ভিডিওতে দক্ষিণ এশিয়ার আঞ্চলিক যোগাযোগের ক্ষেত্রে এই টানেলের অপার সম্ভাবনা ব্যাখ্যা করেছেন।',
                ],
            ],
            'explainer-video-how-biometric-smart-nid-secures-public-services' => [
                'অ্যানিমেটেড ব্যাখ্যা: স্মার্ট জাতীয় পরিচয়পত্রের এনক্রিপশন কীভাবে নাগরিক সেবা নিরাপদ রাখে',
                'ব্যাংক অ্যাকাউন্ট খোলা থেকে শুরু করে জমির খতিয়ান যাচাই—বায়োমেট্রিক চিপের কাজ করার পদ্ধতি।',
                [
                    'স্মার্ট এনআইডির চিপ কীভাবে নাগরিকের তথ্য গোপন ও সুরক্ষিত রাখে তা নিয়ে তৈরি আকর্ষণীয় অ্যানিমেটেড ভিডিও।',
                    '২৫৬-বিট এনক্রিপশন প্রযুক্তির মাধ্যমে ব্যক্তিগত তথ্যের অপব্যবহার পুরোপুরি রোধ করার উপায় সহজ ভাষায় বোঝানো হয়েছে।',
                    'ভবিষ্যতে ই-পাসপোর্ট ও ডিজিটাল স্বাস্থ্য কার্ডের সঙ্গে কীভাবে এটি যুক্ত হবে তা দেখানো হয়েছে।',
                ],
            ],

            // Video: Documentaries
            'voices-from-the-delta-living-with-the-tides' => [
                'ডেল্টার কণ্ঠস্বর: নদীপারের মানুষের সংগ্রামী জীবনের ওপর মিনি ভিডিও ডকুমেন্টারি',
                'নৌকাতেই সংসার, মৌসুমি জোয়ার আর মেঘনার মোহনায় জেগে ওঠা চরের মানুষের অদম্য টিকে থাকার গল্প।',
                [
                    'মেঘনার মোহনায় ভাসমান জীবনযাপন করা পরিবারের গল্প নিয়ে নির্মিত হয়েছে এই মানবিক স্বল্পদৈর্ঘ্য তথ্যচিত্র।',
                    'মাঝিদের মুখে গাওয়া গান আর নদীপারের জীবনযাত্রার চমৎকার ক্যামেরা ফ্রেম আন্তর্জাতিক দর্শকের হৃদয় ছুঁয়েছে।',
                    'ভিডিওটি সামাজিক মাধ্যমে নদীমাতৃক বাংলার আবহমান ঐতিহ্য হিসেবে ব্যাপক প্রশংসিত হয়েছে।',
                ],
            ],
            'field-report-inside-the-national-seed-preservation-vault' => [
                'মাঠপর্যায়ের ভিডিও: গাজীপুরের জাতীয় বীজ সংরক্ষণ ভল্টের ভেতরে এক দুর্লভ ভ্রমণ',
                'মাইনাস বিশ ডিগ্রি তাপমাত্রায় সংরক্ষিত হচ্ছে দেশের হাজার হাজার দেশীয় ধানের দুর্লভ জাত।',
                [
                    'বাংলাদেশ কৃষি গবেষণা ইনস্টিটিউটের অত্যাধুনিক ক্রায়োজেনিক ভল্টের ভেতরের বিশেষ ভিডিও প্রতিবেদন।',
                    'বিজ্ঞানীরা ক্যামেরায় প্রদর্শন করেন কীভাবে খরা ও লবণসহিষ্ণু দেশীয় ফসলের বীজ শত বছরের জন্য অক্ষত রাখা হয়।',
                    'এই ভল্টটি দেশের ভবিষ্যৎ খাদ্য নিরাপত্তার জন্য এক অপরিহার্য জিনগত ঢাল হিসেবে কাজ করছে।',
                ],
            ],

            // Economy
            'remittance-inflows-reach-six-month-high-ahead-of-festivals' => [
                'উৎসবের আগে রেমিট্যান্স প্রবাহে নতুন গতি, ছয় মাসের মধ্যে সর্বোচ্চ',
                'বৈধ পথে প্রণোদনা ও ডিজিটাল ওয়ালেট সুবিধার ফলে প্রবাসীদের অর্থ প্রেরণে রেকর্ড বৃদ্ধি।',
                [
                    'চলতি মাসে ব্যাংকিং চ্যানেলে রেমিট্যান্স আসার পরিমাণ পূর্ববর্তী মাসের সব রেকর্ড ছাড়িয়ে গেছে।',
                    'বৈদেশিক মুদ্রার এই ইতিবাচক প্রবাহ দেশের আমদানি ব্যয় মেটাতে এবং রিজার্ভ স্থিতিশীল রাখতে বড় ভূমিকা রাখছে।',
                    'ব্যাংকগুলো জানিয়েছে, গ্রামে থাকা পরিবারের কাছে এখন তাৎক্ষণিকভাবে রেমিট্যান্সের টাকা পৌঁছে দেওয়া সম্ভব হচ্ছে।',
                ],
            ],
            'inflation-moderates-as-supply-chain-logistics-stabilize' => [
                'সরবরাহ ব্যবস্থা স্বাভাবিক হওয়ায় কমেছে খাদ্য মূল্যস্ফীতি',
                'ফসলের ভালো উৎপাদন ও পরিবহন ব্যয় নিয়ন্ত্রণে আসায় স্বস্তি ফিরেছে নিত্যপণ্যের খুচরা বাজারে।',
                [
                    'সরকারি পরিসংখ্যান ব্যুরোর হালনাগাদ তথ্যে দেখা গেছে, খাদ্যপণ্যের মূল্যে নিম্নমুখী ধারা বজায় রয়েছে।',
                    'কৃষক থেকে সরাসরি ভোক্তার কাছে পণ্য পৌঁছানোর বিশেষ বাজারগুলো মধ্যস্বত্বভোগীদের প্রভাব কমাতে সহায়তা করেছে।',
                    'অর্থনীতিবিদরা পণ্যের দাম সারা বছর স্থিতিশীল রাখতে আধুনিক সাইলো ও কোল্ড স্টোরেজ বাড়ানোর ওপর জোর দিয়েছেন।',
                ],
            ],
            'renewable-energy-investments-surge-across-industrial-zones' => [
                'শিল্পাঞ্চলগুলোতে বাড়ছে রুফটপ সোলার প্যানেলে বিদ্যুৎ উৎপাদনের জোয়ার',
                'কারখানার বিশাল ছাদে সৌরবিদ্যুৎ উৎপাদনে বিদ্যুৎ বিল কমছে এবং কমছে কার্বন নিঃসরণ।',
                [
                    'দেশের শতাধিক বড় রপ্তানিমুখী তৈরি পোশাক কারখানায় ছাদের ওপর সৌরবিদ্যুৎ কেন্দ্র স্থাপন সম্পন্ন হয়েছে।',
                    'কেন্দ্রীয় ব্যাংকের সহজ শর্তের ঋণ সুবিধা ব্যবহার করে কারখানাগুলো গ্রিন এনার্জিতে রূপান্তরিত হচ্ছে।',
                    'শিল্প মালিকেরা জানিয়েছেন, নবায়নযোগ্য জ্বালানি ব্যবহার করায় আন্তর্জাতিক বাজারে তাদের পণ্যের ব্র্যান্ডিং উজ্জ্বল হচ্ছে।',
                ],
            ],

            // Climate
            'mangrove-afforestation-protects-hundreds-of-coastal-villages' => [
                'উপকূলের বিস্তীর্ণ চরে ম্যানগ্রোভ বনায়ন: প্রাকৃতিক ঢালে রক্ষা পাচ্ছে গ্রাম',
                'সবুজ বেষ্টনী তৈরি হওয়ায় ঘূর্ণিঝড়ের তীব্র জলোচ্ছ্বাসের গতি বাধাগ্রস্ত হয়ে কমছে ক্ষয়ক্ষতির ঝুঁকি।',
                [
                    'বন বিভাগ ও স্থানীয় জনসাধারণের যৌথ উদ্যোগে উপকূলের হাজারো হেক্টর চরে লাগানো হয়েছে গোলপাতা ও কেওড়া গাছ।',
                    'ম্যানগ্রোভের ঘন শিকড় মাটির ভাঙন রোধ করছে এবং সেখানে তৈরি হয়েছে দেশীয় মাছের নতুন প্রজননক্ষেত্র।',
                    'বন রক্ষা কমিটির সদস্যরা পাশাপাশি কাঁকড়া চাষ ও মৌমাছি পালনের মাধ্যমে বাড়তি আয় করছেন।',
                ],
            ],
            'early-warning-cyclone-networks-cut-response-times-to-minutes' => [
                'স্মার্ট আগাম সতর্কবার্তা ব্যবস্থার সুফলে দুর্যোগে সাড়াদান সময় কমেছে',
                'স্বয়ংক্রিয় সাইরেন ও মোবাইল মেসেজের মাধ্যমে ঘূর্ণিঝড়ের তথ্য সরাসরি পৌঁছাচ্ছে চরাঞ্চলের মানুষের কাছে।',
                [
                    'দুর্যোগ ব্যবস্থাপনা অধিদপ্তর উপকূলীয় দ্বীপগুলোতে স্বয়ংক্রিয় সংকেত ব্যবস্থা স্থাপন করেছে।',
                    'স্যাটেলাইট তথ্য ও স্থানীয় মেগাফোন সতর্কবার্তার সমন্বয়ে অল্প সময়ে মানুষকে আশ্রয়কেন্দ্রে সরানো সম্ভব হচ্ছে।',
                    'সাম্প্রতিক সচেতনতামূলক মহড়ায় দেখা গেছে, সংকেত পাওয়ার অল্প সময়ের মধ্যেই ঝুঁকিপূর্ণ মানুষ নিরাপদ আশ্রয়ে পৌঁছাতে সক্ষম হচ্ছেন।',
                ],
            ],
            'saline-tolerant-crop-varieties-expand-in-southern-polders' => [
                'দক্ষিণাঞ্চলের লবণাক্ত জমিতে বিজ্ঞানীদের উদ্ভাবিত লবণসহিষ্ণু ফসলের বাম্পার ফলন',
                'বর্ষা পরবর্তী শুষ্ক মৌসুমেও অনাবাদি থাকছে না উপকূলের জমি, চাষ হচ্ছে বিশেষ সরিষা ও ডাল।',
                [
                    'উপকূলীয় পোল্ডার এলাকার কৃষকেরা দেশের বিজ্ঞানীদের উদ্ভাবিত নতুন জাতের লবণসহিষ্ণু ফসল চাষ করে সাফল্য পেয়েছেন।',
                    'যেসব জমি আগে বছরের বেশিরভাগ সময় অনাবাদি থাকত, সেখানে এখন নিয়মিত তেলবীজ ও ডাল জাতীয় ফসল উৎপাদিত হচ্ছে।',
                    'কৃষি সম্প্রসারণ অধিদপ্তর উপকূলের অন্যান্য অঞ্চলেও এই বিশেষ জাতের বীজ বিনা মূল্যে ছড়িয়ে দিচ্ছে।',
                ],
            ],
            'floating-agriculture-baira-farming-expands-in-waterlogged-wetlands' => [
                'জলমগ্ন হাওর ও বিলে কচুরিপানার ভাসমান ধাপ চাষে বিপ্লব',
                'কোনো রাসায়নিক সার ছাড়াই প্রাকৃতিকভাবে উৎপাদিত হচ্ছে বিষমুক্ত শাকসবজি ও স্বাস্থ্যকর চারা।',
                [
                    'দক্ষিণাঞ্চলের পিরোজপুর ও গোপালগঞ্জের শতাব্দী প্রাচীন ঐতিহ্যবাহী ভাসমান কৃষি প্রযুক্তি এখন দেশের অন্যান্য জলাভূমিতে ছড়িয়ে পড়ছে।',
                    'পচে যাওয়া কচুরিপানার স্তূপের ওপর লাউ, শিম, পালং শাক ও টমেটোর ফলন দেখে কৃষকদের মুখে হাসি ফুটেছে।',
                    'জাতিসংঘের খাদ্য ও কৃষি সংস্থা এই উদ্ভাবনী চাষাবাদ পদ্ধতিকে বৈশ্বিক গুরুত্বপূর্ণ কৃষি ঐতিহ্য হিসেবে স্বীকৃতি দিয়েছে।',
                ],
            ],

            // Culture
            'pahela-baishakh-preparations-begin-with-traditional-mangol-shobhajatra' => [
                'পহেলা বৈশাখ বরণে চারুকলায় বর্ণিল মঙ্গল শোভাযাত্রার জোর প্রস্তুতি',
                'শান্তি ও সম্প্রীতির বার্তা ছড়িয়ে দিতে চারুকলার শিক্ষার্থীরা তৈরি করছেন লোকজ মোটিফের মুখোশ ও শিল্পকর্ম।',
                [
                    'বাংলা নববর্ষকে স্বাগত জানাতে দেশের সব সাংস্কৃতিক অঙ্গনে শুরু হয়েছে উৎসবমুখর প্রস্তুতি।',
                    'ঢাকা বিশ্ববিদ্যালয়ের চারুকলা অনুষদে রাত-দিন পরিশ্রম করে তৈরি করা হচ্ছে ঐতিহ্যবাহী লোকজ পুতুল ও শান্তির প্রতীকী ভাস্কর্য।',
                    'ইউনেস্কোর স্বীকৃতিপ্রাপ্ত মঙ্গল শোভাযাত্রায় অংশ নিতে বরাবরের মতো এবারও দেশি-বিদেশি মানুষের বিপুল সমাগম প্রত্যাশা করা হচ্ছে।',
                ],
            ],
            'baul-music-archive-unveils-rare-recordings-from-kushtia' => [
                'কুষ্টিয়ার লালন আখড়া ও বাউল গানের দুর্লভ অডিও ডিজিটাল আর্কাইভে উন্মুক্ত',
                'লোকদর্শন ও লালন সাঁইজির অমর বাণী বিশ্বের গবেষকদের জন্য অনলাইনে সংরক্ষণ।',
                [
                    'বাউল গান ও বাংলার লোকদর্শনের ওপর গবেষণার সুবিধার্থে একটি সমৃদ্ধ ডিজিটাল আর্কাইভ সাধারণের জন্য উন্মুক্ত করা হয়েছে।',
                    'একতারা ও দোতারার অনন্য ঝংকারে গ্রামীণ প্রবীণ বাউলদের গাওয়া শত শত দুষ্প্রাপ্য গানের মূল রেকর্ডিং এতে স্থান পেয়েছে।',
                    'আন্তর্জাতিক সংগীত গবেষকরা বলছেন, এই দার্শনিক গানগুলো বাংলার মানবতাবাদী চিন্তাধারাকে বিশ্বদরবারে উজ্জ্বল করেছে।',
                ],
            ],
            'national-book-fair-records-record-footfall-and-youth-authors' => [
                'অমর একুশে বইমেলায় উপচে পড়া ভিড়, তরুণ লেখকদের বই নিয়ে আগ্রহ তুঙ্গে',
                'পাঠক-লেখকের প্রাণবন্ত আড্ডায় মুখরিত বাংলা একাডেমি ও সোহরাওয়ার্দী উদ্যান প্রাঙ্গণ।',
                [
                    'অমর একুশে বইমেলার প্রতিটি বিকেলে বইপ্রেমী মানুষের পদচারণায় মুখরিত হয়ে উঠছে মেলা প্রাঙ্গণ।',
                    'প্রকাশকরা জানিয়েছেন, বিজ্ঞানবিষয়ক বই, গল্প-উপন্যাস ও গবেষণাধর্মী রচনার বিক্রি এবার সবচেয়ে বেশি।',
                    'মেলামঞ্চে নিয়মিত অনুষ্ঠিত হচ্ছে সাহিত্য আলোচনা ও আবৃত্তি সন্ধ্যা, যা তরুণ প্রজন্মকে বই পড়ার প্রতি আকৃষ্ট করছে।',
                ],
            ],
            'folk-craft-fair-in-sonargaon-showcases-centuries-old-wooden-art' => [
                'সোনারগাঁওয়ে মাসব্যাপী লোককারুশিল্প মেলায় দেশীয় কাঠের পুতুল ও নকশিকাঁথার সমাহার',
                '৬৪ জেলার প্রবীণ কারুশিল্পীদের সরাসরি হাতের কাজ প্রদর্শনী দেখতে ছুটে আসছেন হাজারো দর্শনার্থী।',
                [
                    'বাংলাদেশ লোক ও কারুশিল্প ফাউন্ডেশন চত্বরে ঐতিহ্যবাহী লোকজ মেলা উৎসবের আমেজে শুরু হয়েছে।',
                    'প্রবীণ কারুশিল্পীরা কাঠের সূক্ষ্ম কারুকাজ, মাটির টেপাপুতুল ও নিখুঁত নকশিকাঁথা সেলাইয়ের কৌশল প্রদর্শন করছেন।',
                    'মেলা প্রাঙ্গণে বসেছে পিঠার স্টল ও পুতুলনাচ, যা দর্শনার্থীদের আবহমান গ্রামীণ বাংলার সুবাস উপহার দিচ্ছে।',
                ],
            ],

            // Science
            'biochemists-develop-rapid-water-purity-testing-kits' => [
                'সহজে পানির বিশুদ্ধতা পরীক্ষার সাশ্রয়ী টেস্ট স্ট্রিপ উদ্ভাবন করলেন দেশীয় গবেষকরা',
                'কয়েক মিনিটের মধ্যেই আর্সেনিক, ক্ষতিকর ধাতু ও ব্যাকটেরিয়ার উপস্থিতি শনাক্ত করা সম্ভব।',
                [
                    'বিশ্ববিদ্যালয়ের একদল গবেষক সাধারণ মানুষের ব্যবহার উপযোগী কম খরচের পানির বিশুদ্ধতা পরীক্ষার স্ট্রিপ তৈরি করেছেন।',
                    'পানিতে ক্ষতিকর আর্সেনিক বা জীবাণু থাকলে স্ট্রিপের রঙের পরিবর্তনের মাধ্যমে ফলাফল জানা যায়, কোনো ল্যাবরেটরি ছাড়াই।',
                    'জনস্বাস্থ্য প্রকৌশল অধিদপ্তর এই স্ট্রিপগুলো প্রত্যন্ত অঞ্চলের স্বাস্থ্যকর্মীদের মাঝে বিতরণের পরিকল্পনা করেছে।',
                ],
            ],
            'space-research-station-tracks-weather-patterns-with-high-accuracy' => [
                'স্পারসোতে আধুনিক স্যাটেলাইট প্রযুক্তির মাধ্যমে আবহাওয়ার নিখুঁত পূর্বাভাস',
                'বৃষ্টিপাত ও আকস্মিক পাহাড়ি ঢলের আগাম তথ্যে রক্ষা পাচ্ছে কৃষকের মাঠের ফসল।',
                [
                    'মহাকাশ গবেষণা ও দূর অনুধাবন প্রতিষ্ঠান (স্পারসো) তাদের স্যাটেলাইট তথ্য বিশ্লেষণ কেন্দ্রকে অত্যাধুনিক করেছে।',
                    'মেঘমালার গতিবিধি ও নদী অববাহিকার জলস্তর বিশ্লেষণের মাধ্যমে পাঁচ দিন আগেই সম্ভাব্য বন্যার পূর্বাভাস দেওয়া সম্ভব হচ্ছে।',
                    'কৃষিবিদ ও দুর্যোগ ব্যবস্থাপকেরা জানিয়েছেন, এই আগাম তথ্য হাওর ও পাহাড়ি এলাকার ফসল রক্ষায় গুরুত্বপূর্ণ সহায়তা দিচ্ছে।',
                ],
            ],
            'ai-powered-crop-disease-detection-app-rolled-out-for-farmers' => [
                'মোবাইলে ফসলের ছবি তুলে রোগ শনাক্তে কৃত্রিম বুদ্ধিমত্তাভিত্তিক অ্যাপ',
                'ফসলের পাতার ছবি আপলোড করলেই মিলছে বাংলায় সমাধান ও প্রয়োজনীয় ওষুধের পরামর্শ।',
                [
                    'কৃত্রিম বুদ্ধিমত্তা চালিত একটি নতুন মোবাইল অ্যাপ দেশের কৃষকদের রোগবালাই ব্যবস্থাপনায় দারুণ সহায়তা করছে।',
                    'ধান, পাট বা সবজির আক্রান্ত পাতার ছবি তুললেই অ্যাপটি স্বয়ংক্রিয়ভাবে রোগের কারণ ও প্রতিকার প্রদর্শন করে।',
                    'মাঠপর্যায়ে দেখা গেছে, এই প্রযুক্তির কারণে অতিরিক্ত ও অপ্রয়োজনীয় কীটনাশকের ব্যবহার উল্লেখযোগ্যভাবে হ্রাস পেয়েছে।',
                ],
            ],

            // Environment
            'freshwater-dolphin-sanctuaries-see-encouraging-population-rise' => [
                'পদ্মা-যমুনার শুশুক অভয়াশ্রমে আশাব্যঞ্জকভাবে বেড়েছে নদীর শুশুকের সংখ্যা',
                'ক্ষতিকর জালের ব্যবহার রোধ এবং নদী তীরের মানুষের সচেতনতায় রক্ষা পাচ্ছে এই বিপন্ন জলজ প্রাণী।',
                [
                    'নদীগুলোতে ডলফিন বা শুশুকের অবাধ বিচরণের জন্য ঘোষিত সংরক্ষিত এলাকায় আশাব্যঞ্জক সংখ্যাবৃদ্ধি লক্ষ্য করা গেছে।',
                    'স্থানীয় জেলে সম্প্রদায়ের সহায়তায় অবৈধ কারেন্ট জালের ব্যবহার বন্ধ করায় শুশুকের মৃত্যুহার শূন্যের কোঠায় নেমে এসেছে।',
                    'পরিবেশবিদরা বলছেন, নদীতে শুশুকের উপস্থিতি সামগ্রিক জলজ বাস্তুতন্ত্রের সুস্বাস্থ্যের নির্ভরযোগ্য প্রমাণ।',
                ],
            ],
            'plastic-waste-reduction-initiative-cleans-major-urban-canals' => [
                'নগরীর খালগুলো থেকে শত শত টন প্লাস্টিক বর্জ্য অপসারণ ও পুনর্ব্যবহার',
                'নাগরিক উদ্যোগ ও পরিচ্ছন্নতা অভিযানে স্বাভাবিক হয়েছে শহরের পানি নিষ্কাশন ব্যবস্থা।',
                [
                    'নগরীর প্রধান খালগুলো থেকে ভাসমান প্লাস্টিক বর্জ্য অপসারণের জন্য ব্যাপক পরিচ্ছন্নতা অভিযান পরিচালিত হয়েছে।',
                    'সংগৃহীত প্লাস্টিক বর্জ্য বিশেষ কারখানায় প্রক্রিয়াজাত করে ফুটপাতের পেভার টাইলস ও নির্মাণ সামগ্রীতে রূপান্তর করা হচ্ছে।',
                    'সিটি কর্পোরেশন খালের বিভিন্ন মুখে বিশেষ ভাসমান ট্র্যাশ ট্র্যাপ স্থাপন করেছে যাতে নতুন করে বর্জ্য জমতে না পারে।',
                ],
            ],
            'community-forest-guard-groups-awarded-for-conservation-efforts' => [
                'লাউয়াছড়া ও সাতছড়ির চিরহরিৎ বন সুরক্ষায় প্রশংসিত স্থানীয় বন পাহারা দল',
                'গাছ কাটা রোধ ও বন্যপ্রাণীর নিরাপদ আবাসস্থল সংরক্ষণে অনন্য অবদানের স্বীকৃতি।',
                [
                    'চিরহরিৎ বনাঞ্চলের জীববৈচিত্র্য রক্ষায় অনন্য ভূমিকা রাখায় স্থানীয় বন রক্ষা কমিটিকে জাতীয় সম্মাননা প্রদান করা হয়েছে।',
                    'গ্রামবাসী পালাক্রমে পাহারা দিয়ে মূল্যবান বনজ সম্পদ ও বিলুপ্তপ্রায় প্রাণীদের অভয়াশ্রম সুরক্ষিত রাখছেন।',
                    'ইকোট্যুরিজম ও নার্সারি থেকে অর্জিত আয় ব্যয় করা হচ্ছে গ্রামের শিক্ষা ও সামাজিক উন্নয়নমূলক কর্মকাণ্ডে।',
                ],
            ],
            'migratory-bird-sanctuaries-in-jahangirnagar-and-haors-report-record-flocks' => [
                'জাহাঙ্গীরনগর ও হাকালুকির জলাশয়ে রেকর্ড সংখ্যক পরিযায়ী অতিথি পাখির আগমন',
                'নিরাপদ পরিবেশ ও শিকার বন্ধে কঠোর নজরদারির সুফলে সরালি, বালিহাঁস ও ডুবুরি পাখির কলতানে মুখরিত জলাশয়।',
                [
                    'চলতি শীত মৌসুমে দেশের প্রধান জলাশয়গুলোতে পরিযায়ী অতিথি পাখির সংখ্যা পূর্ববর্তী কয়েক বছরের তুলনায় উল্লেখযোগ্যভাবে বেড়েছে।',
                    'বিশ্ববিদ্যালয় প্রশাসন ও স্থানীয় যুবসমাজ অতিথিদের বিরক্ত না করতে ক্যাম্পাসে সচেতনতামূলক প্রচারণা চালাচ্ছেন।',
                    'পাখি বিশেষজ্ঞরা বলছেন, পাখির সুস্থ উপস্থিতি জলাশয়ের সমৃদ্ধ খাদ্যশৃঙ্খল ও প্রাকৃতিক পরিবেশের প্রতীক।',
                ],
            ],

            // Media
            'bangladesh-betar-celebrates-milestone-with-next-gen-audio-portal' => [
                'আধুনিক ডিজিটাল অডিও পোর্টাল ও নিউজ প্ল্যাটফর্ম চালু করল বাংলাদেশ বেতার',
                'বিশ্বজুড়ে কোটি কোটি শ্রোতার জন্য লাইভ রেডিও, পডকাস্ট, আর্কাইভ সংগীত ও বস্তুনিষ্ঠ সংবাদসেবা।',
                [
                    'বাংলাদেশ বেতার তাদের অত্যাধুনিক ডিজিটাল সেবা চালু করেছে, যেখানে যুক্ত হয়েছে লাইভ রেডিও স্ট্রিমিং ও সমৃদ্ধ আর্কাইভ।',
                    'স্মার্টফোন ও ওয়েব ব্রাউজার থেকে এখন সরাসরি শোনা যাচ্ছে দেশের প্রতিটি আঞ্চলিক কেন্দ্রের সম্প্রচার ও তথ্যবহুল অনুষ্ঠানমালা।',
                    'প্রবাসী ও তরুণ প্রজন্মের শ্রোতারা এই প্ল্যাটফর্মের সমৃদ্ধ সাংস্কৃতিক সম্ভার ও দ্রুতগতির সংবাদের ভূয়সী প্রশংসা করছেন।',
                ],
            ],
            'fact-checking-network-partners-with-rural-community-radio' => [
                'গুজব ও ভুয়া তথ্য প্রতিরোধে কমিউনিটি রেডিও ও ফ্যাক্টচেকারদের যৌথ উদ্যোগ',
                'সামাজিক যোগাযোগমাধ্যমের বিভ্রান্তিকর তথ্যের সত্যতা যাচাই করে নিয়মিত বুলেটিন প্রচার।',
                [
                    'অনলাইনের অপতথ্য ও গুজব সম্পর্কে তৃণমূলের মানুষকে সচেতন করতে কমিউনিটি রেডিওগুলোতে বিশেষ ফ্যাক্ট-চেক পর্ব চালু হয়েছে।',
                    'স্বাস্থ্য, সরকারি অনুদান ও সামাজিক বিষয়ে ছড়িয়ে পড়া ভুল তথ্যের সঠিক রূপ তুলে ধরছেন সাংবাদিকেরা।',
                    'স্থানীয় সুশীল সমাজ জানিয়েছে, এই সচেতনতামূলক প্রচারণার ফলে গ্রামীণ মানুষ অনলাইনের প্রতারণা থেকে রক্ষা পাচ্ছেন।',
                ],
            ],
            'journalism-fellowships-awarded-for-investigative-climate-reporting' => [
                'পরিবেশ ও অনুসন্ধানমূলক সাংবাদিকতায় ১২ তরুণ সাংবাদিককে ফেলোশিপ প্রদান',
                'উপকূলের লবণাক্ততা, ভূগর্ভস্থ পানি ও নবায়নযোগ্য জ্বালানির ওপর মাঠপর্যায়ে অনুসন্ধানী প্রতিবেদন তৈরির সুযোগ।',
                [
                    'জাতীয় গণমাধ্যম প্রতিষ্ঠান দেশের প্রতিভাবান সাংবাদিকদের জন্য বিশেষ অনুসন্ধানমূলক ফেলোশিপ ঘোষণা করেছে।',
                    'ফেলোশিপপ্রাপ্ত সাংবাদিকেরা ডেল্টা অঞ্চলের নদীভাঙন, উপকূলের জীবিকা ও শিল্পাঞ্চলের বর্জ্য ব্যবস্থাপনা নিয়ে গভীর অনুসন্ধান করবেন।',
                    'জ্যেষ্ঠ সাংবাদিকেরা তাদের তথ্যভিত্তিক ও মাল্টিমিডিয়া সাংবাদিকতার ওপর প্রয়োজনীয় মেন্টরশিপ ও দিকনির্দেশনা প্রদান করবেন।',
                ],
            ],
            'national-broadcasting-academy-launches-podcast-production-diploma' => [
                'জাতীয় গণমাধ্যম ইনস্টিটিউটে চালু হলো আধুনিক পডকাস্টিং ও অডিও স্টোরিটেলিং ডিপ্লোমা',
                'ডিজিটাল সাউন্ড এডিটিং, স্ক্রিপ্ট রাইটিং ও ভয়েস মড্যুলেশনের ওপর তরুণ সাংবাদিকদের পেশাদার প্রশিক্ষণ।',
                [
                    'জাতীয় গণমাধ্যম প্রতিষ্ঠান আধুনিক পডকাস্ট ও ডিজিটাল সম্প্রচার প্রযুক্তির ওপর নতুন ডিপ্লোমা কোর্স শুরু করেছে।',
                    'প্রশিক্ষণার্থীরা আধুনিক স্টুডিও মাইক্রোফোন ও মাল্টিট্র্যাক অডিও সফটওয়্যার ব্যবহারে সরাসরি দক্ষতা অর্জন করছেন।',
                    'প্রবীণ সম্প্রচারকেরা এই উদ্যোগকে ডিজিটাল যুগে বেতার ও পডকাস্ট শিল্পের নতুন বিকাশ হিসেবে অভিহিত করেছেন।',
                ],
            ],
        ];

        foreach ($news as $position => [$slug, $categorySlugOrName, $title, $summary, $image, $readTime, $minutesAgo, $viewsCount, $isFeatured, $body]) {
            if (! isset($newsBn[$slug])) {
                throw new RuntimeException("Missing Bangla translation for news slug: {$slug}");
            }
            [$titleBn, $summaryBn, $bodyBn] = $newsBn[$slug];

            $categoryModel = NewsCategory::query()
                ->where('slug', $categorySlugOrName)
                ->orWhere('name', $categorySlugOrName)
                ->first();

            $categoryId = $categoryModel?->id;
            $categoryName = $categoryModel?->name ?? $categorySlugOrName;

            NewsArticle::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'news_category_id' => $categoryId,
                'title' => $title,
                'title_bn' => $titleBn,
                'summary' => $summary,
                'summary_bn' => $summaryBn,
                'category' => $categoryName,
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
            // ==========================================
            // --- NEW RELEASE (new-release) ---
            // ==========================================
            ['shurjer-shondhane-2026', 'Shurjer Shondhane: Dawn of the Coast', 'New Release', 'A newly released cinematic drama exploring three generations of coastal fishing families overcoming catastrophic storms to build a solar-powered green sanctuary.', 'New Release', 'watch-river.png', 2026, 'PG', true, [
                ['Dawn at the Estuary', 54, 'The family sets sail at dawn with new navigational telemetry.'],
                ['Rising Tides', 48, 'A sudden storm tests the village solar floodgates.'],
                ['The New Sanctuary', 50, 'Villagers celebrate the completion of the coastal mangrove greenbelt.'],
            ]],
            ['shobuj-poth-ekattor', 'Shobuj Poth: Freedom Trail 1971', 'New Historical Release', 'A gripping new historical miniseries following a secret radio transmitter courier navigating the delta waterways during the liberation struggle.', 'New Release', 'watch-hero.png', 2026, 'PG-13', true, [
                ['Secret Transmitters', 45, 'A clandestine radio relay unit receives secret coded coordinates.'],
                ['Across the Sunderbans', 42, 'Navigating uncharted tidal creeks to evade enemy gunboats.'],
                ['Voice of the Free Nation', 50, 'The first victorious broadcast resonates across liberated soil.'],
            ]],
            ['dhaka-metro-chronicles', 'Dhaka Metro Chronicles', 'New Urban Anthology', 'An anthology series celebrating interconnected human stories, dreams, romances, and serendipitous encounters across the capital skyline.', 'New Release', 'news-tech.png', 2026, 'G', true, [
                ['Platform 4 Encounter', 32, 'Two former school friends meet unexpectedly after fifteen years.'],
                ['Rush Hour Rhythms', 28, 'A street violinist finds an enthusiastic audience on the evening concourse.'],
                ['The Lost Notebook', 30, 'An architect searches for an artist who left behind a journal of urban sketches.'],
            ]],

            // ==========================================
            // --- TRENDING (trending) ---
            // ==========================================
            ['bhoot-shonibar-midnight-tales', 'Bhoot Shonibar: Haunted River Island', 'Trending Mystery', 'The most talked-about late-night horror episode recounting mysterious supernatural sightings and ancient ruins on a submerged river char.', 'Trending', 'watch-hero.png', 2026, '18+', true, [
                ['The Char of Shadows', 40, 'Surveyors unearth centuries-old brass talismans buried in river silt.'],
                ['Echoes in the Mist', 38, 'Night boatmen hear unearthly ghungroo bells ringing over the calm waters.'],
            ]],
            ['shukhi-shongshar-extended', 'Shukhi Shongshar: The Grand Wedding', 'Trending Family Special', 'The trending wedding special where decades-old family rivalries turn into heartwarming reunions and festive celebrations.', 'Trending', 'watch-kids.png', 2026, 'G', true, [
                ['The Haldi Ceremony', 35, 'Traditional songs and turmeric rituals light up the courtyard.'],
                ['A Surprise Guest', 38, 'An uncle returning from abroad after twenty years brings unexpected joy.'],
            ]],
            ['chhaya-shikari-encounters', 'Chhaya Shikari: Cyber Siege', 'Trending Action Thriller', 'The trending season finale tracking a dangerous rogue hacker syndicate targeting national maritime port telemetry.', 'Trending', 'news-tech.png', 2026, '16+', true, [
                ['Ransomware Threat', 44, 'Automated container cranes freeze under an encrypted attack.'],
                ['Counter Strike', 47, 'The cyber taskforce traces the digital footprint to an offshore rogue server.'],
            ]],

            // ==========================================
            // --- LIVE TV (live-tv) ---
            // ==========================================
            ['betar-live-studio-channel', 'Betar National Live Broadcast Channel', '24/7 Studio Live Stream', 'Live studio transmissions, national news updates, parliamentary reviews, and live symphony orchestral performances from Studio 1.', 'Live TV', 'watch-hero.png', 2026, 'G', true, [
                ['Morning Studio Broadcast', 60, 'Live morning devotional songs, farm advisories, and national news bulletin.'],
                ['Evening Cultural Live', 90, 'Live musical concert and poetry recitations broadcast nationwide.'],
            ]],
            ['shangshod-betar-live', 'Sangshad Betar Parliamentary Live Feed', 'Live Parliamentary Sessions', 'Direct gavel-to-gavel live audio and video coverage of National Parliament debates, legislative bills, and prime minister questions.', 'Live TV', 'news-hero.png', 2026, 'G', false, [
                ['Parliamentary Question Hour', 75, 'Ministers answer oral questions from members of parliament on civic infrastructure.'],
                ['National Budget Debate', 120, 'Live deliberations on the national fiscal budget and policy priorities.'],
            ]],

            // ==========================================
            // --- MOVIES (movies) ---
            // ==========================================
            ['shurjer-shondhane', 'Shurjer Shondhane: In Search of Sun', 'Critically Acclaimed Feature', 'A sweeping cinematic story of three resilient families rebuilding their lives after the Great 1970 Bhola Cyclone.', 'Movies', 'watch-river.png', 2026, 'PG', false, [
                ['Full Feature Movie', 118, 'The complete remastered feature presentation in ultra-high-definition.'],
            ]],
            ['nodir-naam-madhumoti', 'Nodir Naam Madhumoti', 'Classic Cinema Masterpiece', 'An epic tale of love, patriotism and moral dilemmas during the Liberation War along the Madhumati River.', 'Movies', 'watch-hero.png', 2025, 'PG', false, [
                ['Full Feature Presentation', 124, 'Remastered historical motion picture with crystal-clear audio.'],
            ]],
            ['palasheer-shesh-prohor', 'Palasheer Shesh Prohor: Shadows of History', 'Historical Period Film', 'A cinematic reimagining of the historic Battle of Plassey, exploring royal palace diplomacy, courage and geopolitical turning points.', 'Movies', 'watch-hero.png', 2026, 'PG-13', false, [
                ['Full Motion Picture', 135, 'The complete remastered historical epic movie.'],
            ]],
            ['surma-parer-kotha', 'Surma Parer Kotha: Voices of the Valley', 'Award-Winning Feature', 'A visually stunning cinematic portrayal of tea garden workers and river communities living along the gentle curves of the Surma.', 'Movies', 'watch-river.png', 2026, 'PG', false, [
                ['Full Feature Film', 112, 'National Award winning cinematic masterpiece.'],
            ]],

            // ==========================================
            // --- SERIES (series) ---
            // ==========================================
            ['chhaya-shikari', 'Chhaya Shikari: The Shadow Hunter', 'Crime Thriller Series', 'A dedicated cyber-detective and a seasoned port inspector unravel a high-stakes smuggling syndicate operating along the coast.', 'Series', 'watch-hero.png', 2026, '16+', true, [
                ['Midnight Cargo', 42, 'A suspicious container at the outer anchorage triggers a clandestine investigation.'],
                ['Encrypted Waters', 45, 'Digital clues point to an offshore server farm masking vessel coordinates.'],
                ['The Final Trap', 48, 'Law enforcement stages a coordinated multi-agency raid before dawn.'],
            ]],
            ['ogrodut-the-pioneers', 'Ogrodut: The Pioneers', 'Inspirational Drama Series', 'Follow the founding engineers, scientists, and educators who built the foundational infrastructure of post-independence Bangladesh.', 'Series', 'news-tech.png', 2026, 'G', false, [
                ['Blueprint for Bridges', 42, 'Engineers rebuild vital railway bridges under immense time pressure.'],
                ['Electrifying the Delta', 40, 'Bringing rural electrification and clean energy to remote village wards.'],
                ['The First Supercomputer', 44, 'Early computer scientists establish the country’s first digital mainframe.'],
            ]],
            ['shongram-o-shanti', 'Shongram O Shanti: Chronicle of Freedom', 'Epic Historical Series', 'An expansive television series spanning decades of resilience, political movements, and social transformations across the Bengal delta.', 'Series', 'news-hero.png', 2026, 'PG-13', false, [
                ['The Morning of February', 48, 'Students gather at Dhaka University for the mother language movement.'],
                ['Winds of Autonomy', 45, 'Mass political mobilizations demand self-determination and equal rights.'],
                ['The Golden Dawn', 50, 'A nation reborn in dignity and freedom after nine months of sacrifice.'],
            ]],

            // ==========================================
            // --- SHORT FILMS (short-films) ---
            // ==========================================
            ['ekti-notun-bhor', 'Ekti Notun Bhor', 'Award-Winning Short Film', 'A mute village boy invents an acoustic flute that can mimic migratory bird calls, uniting feuding neighbours.', 'Short Films', 'watch-kids.png', 2026, 'G', false, [
                ['Short Film', 22, 'Winner of Best Narrative Short at the Asian Youth Independent Film Festival.'],
            ]],
            ['stationer-chheleti', 'Stationer Chheleti: The Boy at the Station', 'Inspiring Short Film', 'A determined young newspaper vendor uses discarded textbooks to study for university admission exams.', 'Short Films', 'news-tech.png', 2026, 'G', false, [
                ['Complete Short Film', 19, 'A heartwarming portrayal of persistence, self-education and community warmth.'],
            ]],
            ['brishitir-shur', 'Brishtir Shur: Symphony of the Rain', 'Artistic Visual Short', 'A visual poem capturing how a sudden afternoon downpour transforms the bustling streets, old rooftops, and courtyard rhythms of Old Dhaka.', 'Short Films', 'watch-river.png', 2026, 'G', false, [
                ['Full Short Film', 16, 'Experimental cinematic short featuring ambient binaural soundscapes.'],
            ]],
            ['shonar-tori-short', 'Shonar Tori: The Golden Boat', 'Poetic Cinema', 'Rabindranath Tagore’s timeless allegorical poem brought to life on the glistening monsoon floodwaters of Shilaidaha.', 'Short Films', 'watch-music.png', 2026, 'G', false, [
                ['Poetic Featurette', 18, 'A lyrical visual adaptation exploring harvest, art and timelessness.'],
            ]],

            // ==========================================
            // --- SONGS (songs) ---
            // ==========================================
            ['desher-gaan-o-shur', 'Desher Gaan O Shur: Patriotic Melodies', 'Grand Orchestral Music Special', 'The Bangladesh Betar National Symphony Orchestra performs stirring renditions of timeless national anthems and folk tunes.', 'Songs', 'watch-music.png', 2026, 'G', false, [
                ['Songs of the Motherland', 45, 'Celebrated vocalists and live symphony orchestra in a breathtaking performance.'],
                ['Rhythms of Freedom', 40, 'Dynamic percussion and traditional folk instrumental ensemble.'],
            ]],
            ['baul-gaan-acoustic-sessions', 'Baul Gaan: Acoustic Riverbank Sessions', 'Unplugged Musical Recordings', 'Master folk minstrels perform mystical Lalon and Hason Raja lyrics beneath ancient banyan trees at sunset.', 'Songs', 'watch-music.png', 2026, 'G', false, [
                ['Mon Amar Moner Moto', 34, 'Soulful ektara and dotara melodies recorded live in Kushtia.'],
                ['Nodi Bhora Dheu', 36, 'Bhatiyali river songs celebrating the eternal cadence of the delta.'],
            ]],
            ['raga-ananda-classical-night', 'Raga Ananda: Midnight Classical Ragas', 'Classical Instrumental', 'Eminent sitar, sarod, and bansuri virtuosos perform meditative nighttime ragas recorded inside the historic Betar auditorium.', 'Songs', 'watch-hero.png', 2026, 'G', false, [
                ['Raga Yaman Awakening', 48, 'Sitar and tabla jugalbandi exploring contemplative evening ragas.'],
                ['Bansuri Raga Megh', 42, 'Haunting bamboo flute melodies evoking the arrival of torrential monsoon clouds.'],
            ]],
            ['bhatiyali-river-rhythms', 'Bhatiyali River Rhythms of the Meghna', 'Folk Heritage Audio', 'Authentic field recordings of boatmen and riverside minstrels singing traditional Bhatiyali, Jari, and Sari folk melodies.', 'Songs', 'watch-river.png', 2026, 'G', false, [
                ['O Re Neel Doriya', 32, 'Timeless sea and river songs celebrating the courage of coastal mariners.'],
                ['Majhir Bhaat Gaan', 30, 'Rhythmic harvesting songs sung during long boat voyages down river.'],
            ]],

            // ==========================================
            // --- DRAMA (drama) ---
            // ==========================================
            ['the-last-transmission', 'The Last Transmission', 'New Original Drama', 'In a radio studio during the final weeks of 1971, a young broadcaster discovers that one carefully chosen message can travel farther than fear.', 'Drama', 'watch-hero.png', 2026, 'PG', true, [
                ['The Signal', 46, 'Maya arrives for a night shift that will change the course of the station.'],
                ['Between Frequencies', 44, 'A hidden message forces the team to decide who they can trust.'],
                ['The Last Transmission', 52, 'The studio prepares one final broadcast as dawn approaches.'],
            ]],
            ['shongshoy-o-shotti', 'Shongshoy O Shotti', 'Courtroom Suspense Drama', 'A fearless public defender takes on a seemingly unwinnable case defending an honest civil engineer.', 'Drama', 'watch-hero.png', 2026, '16+', false, [
                ['The Blueprint', 45, 'Dam construction audit reports vanish right before crucial judicial proceedings.'],
                ['The Cross Examination', 48, 'Dramatic courtroom testimonies expose an intricate corporate conspiracy.'],
            ]],
            ['nil-nodir-tire', 'Nil Nodir Tire: By the Blue Waters', 'Social Drama Special', 'A poignant radio drama exploring family reconciliations, old friendships and forgiveness in a riverside village.', 'Drama', 'watch-river.png', 2026, 'G', false, [
                ['The Letter Across the River', 38, 'A long-lost childhood letter bridges thirty years of silence.'],
                ['The Reunion at Dusk', 40, 'Old friends meet at the village ferry ghat as the sun sets.'],
            ]],
            ['shobuj-pata-drama', 'Shobuj Pata: Leaves of Hope', 'Community Radio Drama', 'A dedicated village school teacher starts an after-hours literacy club for working boat children and tea garden helpers.', 'Drama', 'news-rice.png', 2026, 'G', false, [
                ['The Floating School', 36, 'Classes begin on a renovated roofed wooden cargo boat.'],
                ['The Graduation Lanterns', 38, 'Children light lanterns celebrating their first handwritten letters.'],
            ]],

            // ==========================================
            // --- DOCUMENTARY (documentary) ---
            // ==========================================
            ['rivers-that-remember', 'Rivers That Remember', 'Documentary Series', 'Travel with the boat communities whose stories, livelihoods and songs follow the changing waterways of Bangladesh.', 'Documentary', 'watch-river.png', 2026, 'G', true, [
                ['Morning Tide', 28, 'A fishing family reads the river before sunrise.'],
                ['Moving Banks', 31, 'Communities adapt as familiar channels shift.'],
                ['Songs Downstream', 29, 'Music carries memory from one generation to the next.'],
            ]],
            ['voices-of-betar', 'Voices of Betar', 'Archive Documentary', 'Presenters, engineers and performers revisit the historic moments that made public radio part of everyday national life.', 'Documentary', 'watch-hero.png', 2025, 'G', false, [
                ['Behind the Microphone', 48, 'The legendary people who gave a national broadcaster its timeless voice.'],
                ['Frequencies of Freedom', 50, 'The clandestine broadcast relays of the 1971 Swadhin Bangla Betar Kendra.'],
            ]],
            ['tomorrows-builders', "Tomorrow's Builders", 'Factual Science Series', 'Young student inventors turn classroom ideas into practical agricultural and robotic tools for their communities.', 'Documentary', 'news-tech.png', 2026, 'G', false, [
                ['Small Machines, Big Ideas', 26, 'A rural robotics club prepares for its first national showcase.'],
                ['Solar on the Water', 28, 'Engineering undergraduates build floating solar pumps for irrigation.'],
            ]],
            ['ready-together', 'Ready Together', 'Community Resilience Stories', 'Meet the extraordinary coastal volunteers strengthening local disaster preparedness before severe monsoons arrive.', 'Documentary', 'news-coast.png', 2026, 'G', false, [
                ['The Shelter Team', 27, 'Neighbours turn cyclone preparedness into an empowering shared routine.'],
                ['After the Storm', 29, 'Community brigades restore drinking water tube-wells in record time.'],
            ]],
            ['archeology-of-mahasthangarh', 'Echoes of Mahasthangarh', 'Historical Archaeology Documentary', 'Excavations unveil two millennia of ancient urban civilisations along the banks of the Karatoya River.', 'Documentary', 'watch-river.png', 2026, 'G', false, [
                ['Layers of Time', 34, 'Archaeologists unearth terracotta seals and ancient citadel fortifications.'],
            ]],
            ['sundarbans-living-sanctuary', 'Sundarbans: The Living Mangrove Sanctuary', 'Nature & Wildlife Documentary', 'An extraordinary cinematic exploration of the Royal Bengal tiger, tidal mudflats, and traditional honey collectors in the Sundarbans.', 'Documentary', 'news-coast.png', 2026, 'G', false, [
                ['Guardians of the Tide', 36, 'Forest rangers and honey harvesters navigate dangerous crocodile-inhabited creeks.'],
                ['Kingdom of the Mangroves', 38, 'Rare high-definition footage of apex predators inside deep core zones.'],
            ]],

            // ==========================================
            // --- CULTURE (culture) ---
            // ==========================================
            ['songs-of-the-courtyard', 'Songs of the Courtyard', 'Live Musical Performances', 'An intimate musical evening of classical ragas and soulful folk traditions recorded live with master instrumentalists.', 'Culture', 'watch-music.png', 2026, 'G', false, [
                ['Folk Roads', 42, 'Timeless songs shaped by travel, rivers and village life.'],
                ['Poetry in Raga', 39, 'Classical vocalists and sitar maestros meet in a spellbinding arrangement.'],
            ]],
            ['heritage-crafts-of-bengal', 'Living Heritage: Master Crafts of Bengal', 'Folk Art & Heritage', 'Discover the master weavers of Jamdani, rural potters of Dhamrai, and embroiderers of Nakshi Kantha preserving timeless craft traditions.', 'Culture', 'news-rice.png', 2026, 'G', false, [
                ['Threads of Jamdani', 32, 'Weavers in Narayanganj weave intricate floral patterns on handlooms.'],
                ['Clay and Fire', 28, 'Traditional potters create terracotta figurines and clay cooking pots.'],
            ]],
            ['lalon-shah-mystic-poetry', 'Lalon Shah: Philosophy of the Human Soul', 'Spiritual Philosophy Series', 'A profound cultural exploration of mystic philosopher Lalon Shah’s egalitarian teachings, secular humanism, and universal songs.', 'Culture', 'watch-hero.png', 2026, 'G', false, [
                ['Manush Bhojle Shonar Manush Hobi', 40, 'Scholars and baul singers discuss the core philosophical verses of Lalon.'],
                ['Echoes of the Akhra', 38, 'Midnight gathering of singers under the holy banyan trees of Chheuriya.'],
            ]],
            ['festivals-of-the-delta', 'Festivals of the Delta', 'Heritage Cultural Series', 'Experience the pulsating colours, sacred chants, boat races and carnivals that celebrate seasonal transitions.', 'Culture', 'watch-music.png', 2026, 'G', false, [
                ['The Great Boat Race', 30, 'Rowers sing rhythmic sari gaan as long racing boats slice through river waves.'],
                ['Harvest Lanterns', 28, 'Villagers light thousand terracotta lamps during rural autumn celebrations.'],
            ]],

            // ==========================================
            // --- KIDS (kids) ---
            // ==========================================
            ['little-field-guides', 'Little Field Guides', 'New for Young Explorers', 'Curious children discover the plants, insects and wildlife living just beyond their classroom windows.', 'Kids', 'watch-kids.png', 2026, 'G', false, [
                ['Life on a Lily Pad', 14, 'Meet the tiny aquatic neighbours inhabiting a village pond.'],
                ['The Busy Banyan', 13, 'A single ancient tree becomes a bustling haven for hundreds of species.'],
                ['After the Rain', 15, 'Young nature explorers follow the clues left by fresh monsoon showers.'],
            ]],
            ['shurjer-hasi-kids-tales', 'Shurjer Hasi: Moral Fables for Young Minds', 'Children Storytime', 'Delightful animated audio stories featuring talking animals, clever children, and valuable lessons in empathy, honesty, and kindness.', 'Kids', 'watch-kids.png', 2026, 'G', false, [
                ['The Clever Squirrel', 16, 'A witty squirrel teaches the forest animals how to save food for the rains.'],
                ['The Honest Woodcutter', 18, 'A classic folk fable about truthfulness and unexpected rewards.'],
            ]],
            ['robot-chhotoder-biggan', 'Chhotoder Biggan: Science Adventures with Robi', 'Interactive Kids STEM', 'An interactive science adventure where Robi the friendly robot explains solar planets, gravity, rainbows, and plant photosynthesis.', 'Kids', 'news-tech.png', 2026, 'G', false, [
                ['Journey to the Moon', 20, 'Exploring craters, zero gravity, and space rockets with interactive quizzes.'],
                ['Why Rain Falls', 18, 'Learning the water cycle and cloud formation through fun animated experiments.'],
            ]],
            ['tuntuni-o-dustu-bagh', 'Tuntuni O Dustu Bagh: Animated Fables', 'Classic Bengali Children Tales', 'The clever little tailorbird outsmarts a greedy forest tiger in this beloved, joyful animated folk tale.', 'Kids', 'watch-kids.png', 2026, 'G', false, [
                ['The Bird and the King', 15, 'Tuntuni builds a warm nest and teaches the pompous king a lesson.'],
                ['The Tiger and the Honey Pot', 17, 'A delightful forest comedy full of witty rhymes and sound effects.'],
            ]],

            // ==========================================
            // --- COMEDY (comedy) ---
            // ==========================================
            ['bhalobashar-koutuk', 'Bhalobashar Koutuk', 'Classic Comedy Theatre', 'A witty village matchmaker gets entangled in his own comical misunderstandings during wedding preparations.', 'Comedy', 'watch-kids.png', 2026, 'G', false, [
                ['The Letter Mix-up', 25, 'Two identical letters sent to different households spark hilarious confusion.'],
                ['The Fake Astrologer', 28, 'A clever scheme to reveal true love leads to riotous village laughter.'],
            ]],
            ['gramer-hasir-golpo', 'Gramer Hasir Golpo', 'Rural Satire and Humour', 'Witty everyday encounters between clever tea-stall philosophers, eccentric headmasters, and village youth.', 'Comedy', 'news-rice.png', 2026, 'G', false, [
                ['The Great Tea Debate', 22, 'A debate over football tactics consumes the entire village market.'],
                ['The Modern Bicycle', 24, 'The postman buys a smart electric bicycle with unexpected talking features.'],
            ]],
            ['chayer-dokane-torko', 'Chayer Dokane Torko: Tea Stall Debates', 'Satirical Comedy Series', 'Hilarious daily arguments between passionate football fans, local politicians, and village elders over a single cup of milk tea.', 'Comedy', 'news-hero.png', 2026, 'G', false, [
                ['World Cup Fever', 26, 'Supporters of rival teams draft an imaginary peace treaty on a biscuit carton.'],
                ['The Smart Phone Guru', 24, 'The village elder tries to install an AI farming assistant with comical results.'],
            ]],
            ['dactar-babu-ashen-ni', 'Dactar Babu Ashen Ni: Village Hospital Comedy', 'Lighthearted Comedy Drama', 'A medical assistant and a dramatic compounder try to manage a bustling village health clinic when the doctor gets stuck in traffic.', 'Comedy', 'watch-hero.png', 2026, 'G', false, [
                ['The Stethoscope Dilemma', 25, 'Diagnosing hypochondriac village elders with sweet candy pills.'],
                ['Emergency in the Waiting Room', 27, 'A theatre actor fakes fainting to get immediate medicine for his headache.'],
            ]],

            // ==========================================
            // --- LIVING AND CULTURE (living-and-culture) ---
            // ==========================================
            ['monsoon-kitchen', 'The Monsoon Kitchen', 'Food and Cultural Journeys', 'Celebrated regional home cooks share seasonal monsoon recipes, culinary secrets, and family histories.', 'Living and Culture', 'news-rice.png', 2026, 'G', true, [
                ['First Rain Delicacies', 24, 'A traditional feast built around the arrival of fresh monsoon rains.'],
                ['Hilsa and Mustard Dreams', 26, 'Authentic riverbank cooking techniques passed down through generations.'],
            ]],
            ['shitol-patir-deshe', 'Shitol Patir Deshe: Weaving the Cool Mats of Sylhet', 'Artisanal Living Series', 'Follow the traditional Murta cane weavers in Sylhet creating UNESCO-recognized Shitol Pati mats known for their natural cooling qualities.', 'Living and Culture', 'watch-river.png', 2026, 'G', false, [
                ['Harvesting the Murta Cane', 26, 'Splitting cane stems into paper-thin glossy ribbons.'],
                ['Patterns of the Lotus', 28, 'Weaving intricate geometric birds and water-lilies into heritage mats.'],
            ]],
            ['pitha-parbon-heritage', 'Pitha Parbon: Traditional Winter Sweets of Bengal', 'Culinary Traditions', 'A heartwarming culinary journey across rural households preparing Bhapa, Chitoi, Patishapta, and Dudh Puli with freshly harvested date palm jaggery.', 'Living and Culture', 'news-rice.png', 2026, 'G', false, [
                ['The Date Palm Tapper', 25, 'Collecting sweet earthen sap pots from tall palm trees at dawn.'],
                ['Courtyard Fire and Steam', 27, 'Grandmothers and aunts gather around clay stoves making delicate winter delicacies.'],
            ]],
            ['nakshi-kanthar-khoje', 'Nakshi Kanthar Khoje: Stories in Stitches', 'Living Craft & Storytelling', 'Elderly women in rural Mymensingh stitch centuries of personal history, village folklore, and river tales into vibrant embroidered quilts.', 'Living and Culture', 'watch-music.png', 2026, 'G', false, [
                ['The Red Border Quilt', 28, 'A grandmother stitches the story of her village during the monsoon flood.'],
                ['Motifs of the Peacock', 30, 'Young apprentices learn the complex double-sided running stitch technique.'],
            ]],

            // ==========================================
            // --- HORROR (horror) ---
            // ==========================================
            ['bhoot-shonibar', 'Bhoot Shonibar: Midnight Radio Tales', 'Spine-Chilling Horror Anthology', 'A late-night radio host reads verified listener encounters with the supernatural from remote corners of Bengal.', 'Horror', 'watch-hero.png', 2026, '18+', true, [
                ['The Abandoned Zamindar Bari', 38, 'A group of college researchers spends a stormy night inside a haunted mansion.'],
                ['Whispers in the Fog', 35, 'A lone boatman hears haunting melodies drifting from a submerged river island.'],
                ['The Red Trunk', 40, 'An antique heirloom chest brings eerie premonitions to its new owners.'],
            ]],
            ['raater-chhaya', 'Raater Chhaya: Shadows of Midnight', 'Supernatural Thriller', 'An investigative paranormal journalist probes mysterious nocturnal sightings in the misty tea gardens of Sylhet.', 'Horror', 'watch-hero.png', 2026, '16+', false, [
                ['The Ghost of Estate 7', 36, 'Strange occurrences baffle night guards in the oldest British-era tea estate.'],
                ['The Vanishing Trail', 38, 'Footprints that lead into deep forest ravines and suddenly disappear.'],
            ]],
            ['nodi-kuler-pretopuri', 'Nodi Kuler Pretopuri', 'Haunted River Folklore', 'A documentary crew investigating sunken shipwrecks discovers forgotten legends that refuse to stay submerged.', 'Horror', 'watch-river.png', 2026, '16+', false, [
                ['Under the Dark Water', 32, 'Sonar scans reveal an ancient vessel not listed in any maritime archive.'],
                ['The Phantom Ferry', 34, 'Night travelers witness a ghost ferry crossing misty river channels.'],
            ]],
            ['jongol-barir-rahasya', 'Jongol Barir Rahasya: The Mystery of Jungle Bari', 'Suspense & Horror Series', 'An urban family inherits a remote ancestral palace surrounded by deep marshlands, only to discover eerie secrets locked in the clock tower.', 'Horror', 'watch-hero.png', 2026, '16+', false, [
                ['The Stopped Clock', 33, 'The clock tower strikes twelve despite having no working gears.'],
                ['Footsteps on the Roof', 35, 'Heavy footsteps pace the rooftop terrace during thunderstorm nights.'],
            ]],

            // ==========================================
            // --- NEWS AND CURRENT AFFAIRS (news-and-current-affairs) ---
            // ==========================================
            ['betar-shongbad-bortika', 'Betar Shongbad Bortika', 'Weekly Current Affairs Analysis', 'Senior journalists and economic policy experts dissect the biggest national and global headlines of the week.', 'News and Current Affairs', 'news-hero.png', 2026, 'G', false, [
                ['The Economic Horizon', 40, 'Deep dive into national budget allocations and export diversification.'],
                ['Diplomacy in Focus', 38, 'Analyzing South Asian trade pacts and climate financing accords.'],
            ]],
            ['mukhomukhi-bortoman', 'Mukhomukhi Bortoman', 'Hard-Hitting Interview Series', 'Cabinet ministers, civic leaders, and innovators answer direct citizen questions on public accountability.', 'News and Current Affairs', 'news-tech.png', 2026, 'G', false, [
                ['Digital Governance Dialogue', 42, 'The ICT Minister discusses citizen service automation and data privacy.'],
                ['Public Transport Overhaul', 40, 'City planners debate urban rapid bus transit expansion.'],
            ]],
            ['desh-o-durniti', 'Desh O Durniti: Investigative Reporting Forum', 'Investigative Public Affairs', 'A hard-hitting investigative journalism show examining transparency in public infrastructure spending, tax reforms, and consumer rights.', 'News and Current Affairs', 'news-coast.png', 2026, 'G', false, [
                ['Procurement Transparency', 44, 'Auditing major highway construction tenders and materials testing standards.'],
                ['Protecting the Consumer', 38, 'Field investigations into food adulteration testing labs and market monitoring.'],
            ]],
            ['shomoyer-shondhane', 'Shomoyer Shondhane: Weekly National Analysis', 'National Policy Round-up', 'In-depth panel discussions with leading economists, environmentalists and sociologists on national development priorities.', 'News and Current Affairs', 'news-hero.png', 2026, 'G', false, [
                ['The Delta Blueprint', 45, 'Hydrologists discuss dredging mega-projects along major river basins.'],
                ['Future of Youth Employment', 42, 'Assessing vocational skill centers and global freelance market demand.'],
            ]],

            // ==========================================
            // --- POPULAR PROGRAMMES (popular-programmes) ---
            // ==========================================
            ['shukhi-shongshar', 'Shukhi Shongshar', 'Popular Family Drama', 'Generations navigate love, career ambitions and cherished rural traditions in a vibrant multi-generational riverside home.', 'Popular Programmes', 'watch-kids.png', 2026, 'G', true, [
                ['The Family Feast', 32, 'Relatives gather for the annual harvest celebration with surprise announcements.'],
                ['New Beginnings', 30, 'The youngest daughter receives a scholarship to study renewable energy.'],
                ['Bridges of Understanding', 34, 'Elders and youth find common ground on modernizing the family craft studio.'],
            ]],
            ['krishi-shomachar-betar', 'Krishi Shomachar: Modern Farmer Magazine', 'Flagship Agricultural Show', 'The beloved agricultural broadcast bringing actionable weather advisories, high-yield organic farming methods, and inspiring farmer success stories.', 'Popular Programmes', 'news-rice.png', 2026, 'G', false, [
                ['Smart Drip Irrigation', 30, 'How solar water pumps cut operational diesel fuel costs by seventy percent.'],
                ['Organic Bio-Pest Defense', 28, 'Using natural neem extract formulations to protect vegetable plots.']
            ]],
            ['shadhin-bangla-betar-kotha', 'Shadhin Bangla Betar Kotha: Voices of 1971', 'Beloved Historical Radio Show', 'A cherished commemorative radio program interviewing freedom fighters, vocalists, and announcers who inspired a nation to victory.', 'Popular Programmes', 'watch-hero.png', 2026, 'G', false, [
                ['Songs That Ignited Courage', 42, 'The recording of timeless patriotic anthems during the liberation struggle.'],
                ['Extreme Radio Relays', 40, 'Broadcasting under shellfire with makeshift antenna masts on border posts.']
            ]],
            ['shorgo-motro-patal', 'Shorgo Motro Patal: Mythological Musical Special', 'Grand Radio Musical Drama', 'A legendary musical radio play blending classical vocal ensemble, witty folk banter, and ethical dilemmas.', 'Popular Programmes', 'watch-music.png', 2026, 'G', false, [
                ['The Debate in Heaven', 35, 'Gods and mortals debate the true definition of happiness on earth.'],
                ['The Song of Unity', 38, 'An orchestral finale harmonizing traditional instruments and choral melodies.'],
            ]],

            // ==========================================
            // --- CRIME DRAMA (crime-drama) ---
            // ==========================================
            ['chhaya-shikari-crime', 'Chhaya Shikari: Port Mystery', 'Crime Thriller Series', 'A dedicated cyber-detective and a seasoned port inspector unravel a high-stakes smuggling syndicate operating along the coast.', 'Crime Drama', 'watch-hero.png', 2026, '16+', true, [
                ['Midnight Cargo', 42, 'A suspicious container at the outer anchorage triggers a clandestine investigation.'],
                ['Encrypted Waters', 45, 'Digital clues point to an offshore server farm masking vessel coordinates.'],
                ['The Final Trap', 48, 'Law enforcement stages a coordinated multi-agency raid before dawn.'],
            ]],
            ['raater-shongket', 'Raater Shongket: Code of the Night', 'Procedural Police Drama', 'An elite CID homicide unit uses cutting-edge forensic science to solve complex locked-room mysteries in Dhaka.', 'Crime Drama', 'watch-hero.png', 2026, '16+', false, [
                ['The Silent Witness', 44, 'A missing artist’s sketchbook holds the key to an elaborate art forgery heist.'],
                ['Digital Shadows', 46, 'Cyber-forensics teams trace encrypted transactions behind high-profile extortion.'],
            ]],
            ['kuashay-onusondhan', 'Kuashay Onusondhan: Investigation in the Fog', 'Investigative Crime Thriller', 'A seasoned detective is called to an old railway junction in northern Bengal to solve the vanishing of a guarded cash carriage.', 'Crime Drama', 'watch-river.png', 2026, '16+', false, [
                ['The Signal Box Phantom', 42, 'The railway signal was tampered with minutes before the train disappeared.'],
                ['Tracking the Red Wagon', 45, 'Footprints in the mustard fields lead detectives to an abandoned sugar mill.'],
            ]],
        ];

        $showsBn = [
            'shurjer-shondhane-2026' => ['সূর্যের সন্ধানে: উপকূলের নতুন ভোর', 'নতুন মুক্তি', 'উপকূলীয় অঞ্চলের জেলে পরিবারের তিন প্রজন্মের জীবনসংগ্রাম ও দুর্যোগ মোকাবিলা করে সবুজ আবাস গড়ে তোলার নতুন মুক্তিপ্রাপ্ত সিনেমা।'],
            'shobuj-poth-ekattor' => ['সবুজ পথ: একাত্তরের মুক্তির গান', 'নতুন ঐতিহাসিক নাটক', 'মুক্তিযুদ্ধ চলাকালে নদীপথে গোপন বেতার ট্রান্সমিটার পৌঁছে দেওয়ার রোমাঞ্চকর অভিযান নিয়ে নতুন মিনিসিরিজ।'],
            'dhaka-metro-chronicles' => ['ঢাকা মেট্রো ক্রনিকলস', 'নগর জীবনের নতুন গল্প', 'রাজধানীর বুকে মেট্রোরেলের নিত্যযাত্রীদের দৈনন্দিন স্বপ্ন, ভালোবাসা ও মানবিক মেলবন্ধনের নতুন ধারাবাহিক।'],
            'bhoot-shonibar-midnight-tales' => ['ভূত শনিবার: নদীকূলের প্রেতগাথা', 'ট্রেন্ডিং রহস্যগাথা', 'পদ্মার চরে জেগে ওঠা প্রাচীন রাজবাড়ির ধ্বংসাবশেষে ঘটে যাওয়া রোমহর্ষক সত্য ভৌতিক ঘটনার বিশেষ পর্ব।'],
            'shukhi-shongshar-extended' => ['সুখী সংসার: মহোৎসব', 'পারিবারিক বিশেষ পর্ব', 'পারিবারিক মান-অভিমান ভুলে পুরো গ্রামজুড়ে আনন্দের বন্যা নিয়ে নির্মিত মহোৎসবের বিশেষ পর্ব।'],
            'chhaya-shikari-encounters' => ['ছায়া শিকারী: সাইবার জাল', 'অ্যাকশন থ্রিলার', 'চট্টগ্রাম বন্দরের ডিজিটাল নিরাপত্তা ব্যবস্থাকে লক্ষ্য করে সাইবার আক্রমণের বিরুদ্ধে রুদ্ধশ্বাস অভিযান।'],
            'betar-live-studio-channel' => ['বাংলাদেশ বেতার সরাসরি সম্প্রচার চ্যানেল', 'সরাসরি স্টুডিও লাইভ', 'স্টুডিও ১ থেকে প্রতিদিনের সরাসরি খবর, আলোচনা, গান ও বিশেষ জাতীয় দিবসের অনুষ্ঠানমালা।'],
            'shangshod-betar-live' => ['সংসদ বেতার সরাসরি সম্প্রচার', 'সংসদ অধিবেশন লাইভ', 'জাতীয় সংসদের অধিবেশন, প্রধানমন্ত্রীর প্রশ্নোত্তর পর্ব ও গুরুত্বপূর্ণ বিলের সরাসরি সম্প্রচার।'],
            'shurjer-shondhane' => ['সূর্যের সন্ধানে', 'পুরস্কারপ্রাপ্ত পূর্ণদৈর্ঘ্য চলচ্চিত্র', 'উপকূলের মানুষের প্রতিকূলতার বিরুদ্ধে লড়াই ও টিকে থাকার মহাকাব্যিক সিনেমা।'],
            'nodir-naam-madhumoti' => ['নদীর নাম মধুমতী', 'কালজয়ী মুক্তিযুদ্ধভিত্তিক সিনেমা', 'মধুমতী নদীর তীরে দেশপ্রেম ও আত্মত্যাগের অবিস্মরণীয় মুক্তিযুদ্ধের কাহিনী।'],
            'palasheer-shesh-prohor' => ['পলাশীর শেষ প্রহর', 'ঐতিহাসিক চলচ্চিত্র', 'নবাব সিরাজউদ্দৌলার শেষ দিনগুলোর দেশপ্রেম, রাজদরবারের রাজনীতি ও ঐতিহাসিক পলাশীর যুদ্ধের চলচ্চিত্রায়ন।'],
            'surma-parer-kotha' => ['সুরমা পারের কথা', 'পুরস্কারপ্রাপ্ত চলচ্চিত্র', 'সিলেটের সুরমা নদীর পারের মানুষ ও চা শ্রমিকদের জীবনগাথা নিয়ে নির্মিত নান্দনিক পূর্ণদৈর্ঘ্য চলচ্চিত্র।'],
            'chhaya-shikari' => ['ছায়া শিকারী', 'ক্রাইম থ্রিলার ধারাবাহিক', 'এক মেধাবী সাইবার গোয়েন্দা ও বন্দর পরিদর্শক উপকূলীয় এলাকায় সক্রিয় আন্তর্জাতিক চোরাচালান চক্রের রহস্য উন্মোচন করেন।'],
            'ogrodut-the-pioneers' => ['অগ্রদূত: নতুন দিগন্ত', 'অনুপ্রেরণাদায়ী ধারাবাহিক', 'স্বাধীনতার পর যুদ্ধবিধ্বস্ত দেশের সেতু, রেলপথ ও বিজ্ঞান গবেষণাগার গড়ে তোলা অগ্রদূতদের সংগ্রামী জীবনের ধারাবাহিক নাটক।'],
            'shongram-o-shanti' => ['সংগ্রাম ও শান্তি', 'ঐতিহাসিক ধারাবাহিক', 'পঞ্চাশের দশকের ভাষা আন্দোলন থেকে শুরু করে মুক্তিযুদ্ধ পর্যন্ত বাংলার সামাজিক ও সাংস্কৃতিক জাগরণের মহাকাব্যিক ধারাবাহিক।'],
            'ekti-notun-bhor' => ['একটি নতুন ভোর', 'স্বল্পদৈর্ঘ্য চলচ্চিত্র', 'এক কিশোরের বাঁশির সুরে পাড়া-প্রতিবেশীর বিরোধ মিটে যাওয়ার মানবিক গল্প।'],
            'stationer-chheleti' => ['স্টেশনের ছেলেটি', 'অনুপ্রেরণাদায়ী শর্ট ফিল্ম', 'অদম্য ইচ্ছাশক্তির জোরে রেলওয়ে স্টেশনের হকার থেকে বিশ্ববিদ্যালয়ে সুযোগ পাওয়া এক কিশোরের জীবনযুদ্ধ।'],
            'brishitir-shur' => ['বৃষ্টির সুর', 'নান্দনিক স্বল্পদৈর্ঘ্য চলচ্চিত্র', 'পুরান ঢাকার অলিতে-গলিতে বৃষ্টির অপরূপ ছন্দ ও মানুষের অনুভূতির নান্দনিক প্রকাশ।'],
            'shonar-tori-short' => ['সোনার তরী', 'কাব্যিক চলচ্চিত্র', 'শিলাইদহের পদ্মার বুকে রবীন্দ্রনাথ ঠাকুরের অমর কবিতা সোনার তরীর নান্দনিক চলচ্চিত্ররূপ।'],
            'desher-gaan-o-shur' => ['দেশের গান ও সুর', 'জাতীয় ঐকতান পরিবেশনা', 'বেতারের সিম্ফনি অর্কেস্ট্রা এবং দেশের শীর্ষ শিল্পীদের কালজয়ী দেশাত্মবোধক গান।'],
            'baul-gaan-acoustic-sessions' => ['বাউল গান: নদীপারের সুর', 'অ্যাকোস্টিক লাইভ সেশন', 'কুষ্টিয়ার পদ্মাপারে লালন ও হাছন রাজার অমর বাণীর মরমী লাইভ পরিবেশনা।'],
            'raga-ananda-classical-night' => ['রাগ আনন্দ: মধ্যরাতের শাস্ত্রীয় সংগীত', 'শাস্ত্রীয় সংগীত আসর', 'বেতারের স্টুডিওতে সরাসরি পরিবেশিত গভীর রাতের রাগ ইমন, রাগ মেঘ ও দরবারি কানাড়া।'],
            'bhatiyali-river-rhythms' => ['মেঘনার ভাটিয়ালি গানের সুর', 'ভাটি অঞ্চলের লোকসংগীত', 'মেঘনা ও ব্রহ্মপুত্র নদের মাঝি ও লোকশিল্পীদের গাওয়া চিরন্তন ভাটিয়ালি ও জারি-সারি গান।'],
            'the-last-transmission' => ['শেষ সম্প্রচার', 'নতুন মৌলিক নাটক', '১৯৭১ সালের শেষ সপ্তাহে একটি বেতারকেন্দ্রে এক তরুণ সম্প্রচারক আবিষ্কার করে—সঠিকভাবে বেছে নেওয়া একটি বার্তা ভয়কেও অতিক্রম করতে পারে।'],
            'shongshoy-o-shotti' => ['সংশয় ও সত্য', 'আদালতকেন্দ্রিক থ্রিলার', 'এক তরুণ সৎ প্রকৌশলীর বিরুদ্ধে আনা মিথ্যা অভিযোগের বিরুদ্ধে আইনজীবীর আইনি লড়াই।'],
            'nil-nodir-tire' => ['নীল নদীর তীরে', 'সামাজিক পারিবারিক নাটক', 'নদীভাঙনে বদলে যাওয়া গ্রামে দুই পরিবারের মধ্যে অতীত বিরোধ ভুলে এক হওয়ার আবেগঘন কাহিনী।'],
            'shobuj-pata-drama' => ['সবুজ পাতা', 'গ্রামীণ শিক্ষা ও মানবিক নাটক', 'নদী পারের সুবিধাবঞ্চিত শিশুদের জন্য ভাসমান নৌকায় পাঠশালা গড়ে তোলার মানবিক উদ্যোগের গল্প।'],
            'rivers-that-remember' => ['স্মৃতিবাহী নদী', 'প্রামাণ্যচিত্র সিরিজ', 'বাংলাদেশের পরিবর্তনশীল জলপথ ঘিরে নৌকা সম্প্রদায়ের গল্প, জীবিকা ও গানের সঙ্গে ভ্রমণ করুন।'],
            'voices-of-betar' => ['বেতারের কণ্ঠ', 'আর্কাইভ প্রামাণ্যচিত্র', 'উপস্থাপক, প্রকৌশলী ও শিল্পীরা জনজীবনের অংশ হয়ে ওঠা বেতারের স্মরণীয় ঐতিহাসিক মুহূর্তগুলো ফিরে দেখেন।'],
            'tomorrows-builders' => ['আগামীর নির্মাতা', 'তথ্যভিত্তিক সিরিজ', 'তরুণ শিক্ষার্থী উদ্ভাবকেরা শ্রেণিকক্ষের বিজ্ঞান ধারণাকে সমাজের ব্যবহারিক কৃষি ও রোবটিক্স সরঞ্জামে রূপ দেন।'],
            'ready-together' => ['একসঙ্গে প্রস্তুত', 'মানুষের গল্প', 'দুর্যোগের আগে স্থানীয় সক্ষমতা ও সচেতনতা বাড়ানো স্বেচ্ছাসেবকদের অনুপ্রেরণাদায়ী গল্প।'],
            'archeology-of-mahasthangarh' => ['মহাস্থানগড়ের প্রতিধ্বনি', 'ঐতিহাসিক প্রত্নতত্ত্ব প্রামাণ্যচিত্র', 'করতোয়া নদীর তীরে অবস্থিত আড়াই হাজার বছরের প্রাচীন নগর সভ্যতার দুর্লভ প্রত্নতাত্ত্বিক নিদর্শন।'],
            'sundarbans-living-sanctuary' => ['সুন্দরবনের জীবন্ত বাদাবন', 'বন্যপ্রাণী ও প্রকৃতি প্রামাণ্যচিত্র', 'বিশ্ব ঐতিহ্য সুন্দরবনের রয়েল বেঙ্গল টাইগার, চিত্রা হরিণ ও মৌয়ালদের সাহসী জীবনযাত্রা নিয়ে রোমাঞ্চকর প্রামাণ্যচিত্র।'],
            'songs-of-the-courtyard' => ['উঠানের গান', 'সরাসরি পরিবেশনা', 'দেশের শীর্ষ শাস্ত্রীয় ও লোকশিল্পীদের পরিবেশনায় রাগসংগীত ও মাটির গানের অন্তরঙ্গ সন্ধ্যা।'],
            'heritage-crafts-of-bengal' => ['বাংলার আবহমান কারুশিল্প', 'লোকশিল্প ও ঐতিহ্য', 'জামদানি বয়নশিল্পী, ধামরাইয়ের কাঁসা-পিতলের কারিগর ও নকশিকাঁথার সুই-সুতোর কারুকাজের প্রামাণ্য ইতিহাস।'],
            'lalon-shah-mystic-poetry' => ['লালন সাঁইজির মরমি মানবদর্শন', 'মরমি দর্শন ও সংস্কৃতি', 'মহাত্মা লালন শাহের অসাম্প্রদায়িক চেতনা, মানবপ্রেম ও জাতপাতহীন সমাজের মরমি দর্শনের বিশ্লেষণ।'],
            'festivals-of-the-delta' => ['নদীমাতৃক বাংলার উৎসব', 'ঐতিহ্যবাহী সাংস্কৃতিক সিরিজ', 'নৌকাবাইচ, নবান্ন উৎসব ও মেলা নিয়ে বাংলার চিরন্তন আনন্দধারার অনন্য প্রামাণ্যচিত্র।'],
            'little-field-guides' => ['ছোট্ট প্রকৃতি নির্দেশিকা', 'কিশোর অভিযাত্রীদের নতুন আয়োজন', 'কৌতূহলী শিশুরা শ্রেণিকক্ষের বাইরের গাছপালা, পোকামাকড় ও বন্যপ্রাণী আবিষ্কার করে।'],
            'shurjer-hasi-kids-tales' => ['সূর্যের হাসি: শিক্ষণীয় রূপকথা', 'ছোটদের মজার গল্প', 'পশুপাখির মজার গল্প ও ছোটদের জন্য সততা, বন্ধুত্ব ও সহমর্মিতার শিক্ষণীয় চমৎকার রূপকথা।'],
            'robot-chhotoder-biggan' => ['ছোটদের বিজ্ঞান অভিযান', 'ছোটদের বিজ্ঞান ও মহাকাশ', 'রোবট রবির সাথে সৌরজগতের গ্রহ, রংধনু এবং গাছপালার মজার বৈজ্ঞানিক রহস্য উদঘাটন।'],
            'tuntuni-o-dustu-bagh' => ['টুনটুনি ও দুষ্টু বাঘ', 'ছোটদের রূপকথা অ্যানিমেশন', 'বুদ্ধিমান টুনটুনি পাখির চাতুর্যে দুষ্টু বাঘকে শায়েস্তা করার চিরন্তন লোকগাথা।'],
            'bhalobashar-koutuk' => ['ভালোবাসার কৌতুক', 'হাস্যরসাত্মক নাটক', 'গ্রামের এক ঘটকের ভুল বোঝাবুঝি এবং বিয়ে নিয়ে তৈরি মজার নাট্য পরিবেশনা।'],
            'gramer-hasir-golpo' => ['গ্রামের হাসির গল্প', 'গ্রামীণ রম্য ও নাটক', 'চায়ের দোকানের আড্ডা ও গ্রামের বুদ্ধিমান মানুষদের প্রতিদিনের মজার ঘটনার নাট্যরূপ।'],
            'chayer-dokane-torko' => ['চায়ের দোকানের সরস আড্ডা', 'সরস রম্য ধারাবাহিক', 'গ্রামের চায়ের দোকানে বিশ্বকাপ ফুটবল ও দৈনন্দিন রাজনীতি নিয়ে নানা মতের মানুষের সরস তর্কের কমেডি।'],
            'dactar-babu-ashen-ni' => ['ডাক্তার বাবু আসেননি', 'হাসির ধারাবাহিক নাটক', 'ডাক্তার বাবু ট্রাফিকে আটকা পড়ায় সহকারী ও রোগীদের মধ্যে ঘটে যাওয়া হাস্যকর ভুল বোঝাবুঝির হাসির নাটক।'],
            'monsoon-kitchen' => ['বর্ষার রান্নাঘর', 'খাবার ও সংস্কৃতি', 'ঘরের রাঁধুনিরা মৌসুমি বর্ষার সুস্বাদু রেসিপি ও প্রজন্ম ধরে বহমান পারিবারিক রান্নার ঐতিহ্য ভাগ করে নেন।'],
            'shitol-patir-deshe' => ['শীতল পাটির দেশে', 'আবহমান জীবনধারা', 'সিলেটের বিশ্ববিখ্যাত শীতল পাটি তৈরির প্রাচীন কৌশল ও বয়নশিল্পীদের নৈপুণ্য নিয়ে বিশেষ প্রামাণ্য অনুষ্ঠান।'],
            'pitha-parbon-heritage' => ['পিঠা পার্বণের স্বাদ ও গল্প', 'ঐতিহ্যবাহী পিঠা পার্বণ', 'শীতের সকালে খেজুরের নতুন গুড় ও চালের গুঁড়ি দিয়ে তৈরি ঐতিহ্যবাহী ভাঁপা, চিতই ও পাটিসাপটা পিঠার মন জুড়ানো গল্প।'],
            'nakshi-kanthar-khoje' => ['নকশী কাঁথার খোঁজে', 'বাংলার সূচিকর্ম ঐতিহ্য', 'ময়মনসিংহের পল্লী জনপদজুড়ে সুই-সুতোর বুননে সুখ-দুঃখের স্মৃতি জড়ানো নকশিকাঁথার অমর কাহিনী।'],
            'bhoot-shonibar' => ['ভূত শনিবার', 'মধ্যরাতের ভৌতিক কাহিনী', 'মধ্যরাতে বেতারের স্টুডিও থেকে সরাসরি পঠিত সারা দেশের শ্রোতাদের পাঠানো সত্য ভৌতিক ও অলৌকিক অভিজ্ঞতার গল্প।'],
            'raater-chhaya' => ['রাতের ছায়া', 'রহস্য ও ভৌতিক ধারাবাহিক', 'সিলেটের চা বাগানের গভীর রাতে ঘটে যাওয়া অলৌকিক ঘটনা অনুসন্ধানে এক সাংবাদিকের অভিযান।'],
            'nodi-kuler-pretopuri' => ['নদীকূলের প্রেতপুরী', 'ভৌতিক রহস্যগাথা', 'নদীতে ডুবে যাওয়া প্রাচীন জাহাজের রহস্য উদঘাটনে গিয়ে ঘটে যাওয়া ভুতুড়ে অভিজ্ঞতা।'],
            'jongol-barir-rahasya' => ['জঙ্গল বাড়ির রহস্য', 'রহস্য রোমাঞ্চ নাটক', 'হাওড় অঞ্চলের নির্জন রাজবাড়ির শতবর্ষী ঘড়িঘরের ভেতর লুকিয়ে থাকা গা শিউরে ওঠা অজানা রহস্য।'],
            'betar-shongbad-bortika' => ['বেতার সংবাদ বর্তিকা', 'সাপ্তাহিক রাজনৈতিক ও অর্থনৈতিক বিশ্লেষণ', 'সপ্তাহের প্রধান প্রধান জাতীয় ও আন্তর্জাতিক খবরের গভীর বিশ্লেষণ নিয়ে বিশেষ পর্যালোচনা।'],
            'mukhomukhi-bortoman' => ['মুখোমুখি বর্তমান', 'অনুসন্ধানী সাক্ষাৎকার অনুষ্ঠান', 'জনগুরুত্বপূর্ণ নানা বিষয়ে মন্ত্রী ও নীতিনির্ধারকদের সাথে সরাসরি প্রশ্নোত্তর।'],
            'desh-o-durniti' => ['দেশ ও জনস্বার্থ সংলাপ', 'অনুসন্ধানী জনস্বার্থ অনুষ্ঠান', 'উন্নয়ন প্রকল্পের ব্যয় স্বচ্ছতা, ব্যাংকিং খাত ও ভোক্তা অধিকার সুরক্ষায় অনুসন্ধানী প্রতিবেদন ও বিশেষজ্ঞ মতামত।'],
            'shomoyer-shondhane' => ['সময়ের সন্ধানে', 'জাতীয় নীতি ও উন্নয়ন পর্যালোচনা', 'ডেল্টা প্ল্যান, জলবায়ু পরিবর্তন ও উচ্চশিক্ষা সংস্কার নিয়ে বিশেষজ্ঞদের সাথে বিস্তারিত গোলটেবিল বৈঠক।'],
            'shukhi-shongshar' => ['সুখী সংসার', 'জনপ্রিয় পারিবারিক ধারাবাহিক', 'নদীমাতৃক এক যৌথ পরিবারের প্রজন্মের পর প্রজন্মের ভালোবাসা, মান-অভিমান ও সাংস্কৃতিক ঐতিহ্যের গল্প।'],
            'krishi-shomachar-betar' => ['বেতার কৃষি সমাচার', 'জনপ্রিয় কৃষি ম্যাগাজিন', 'কৃষকদের জন্য আধুনিক চাষাবাদ, উচ্চফলনশীল জাত, আবহাওয়া পূর্বাভাস ও সফল খামারিদের অনুপ্রেরণাদায়ী অনুষ্ঠান।'],
            'shadhin-bangla-betar-kotha' => ['স্বাধীন বাংলা বেতার কথা', 'জনপ্রিয় ইতিহাসভিত্তিক অনুষ্ঠান', 'একাত্তরের শব্দসৈনিক, শিল্পী ও বীর মুক্তিযোদ্ধাদের স্মৃতিচারণ নিয়ে জনপ্রিয় ইতিহাসভিত্তিক অনুষ্ঠান।'],
            'shorgo-motro-patal' => ['স্বর্গ মর্ত্য পাতাল', 'জনপ্রিয় গীতি-নকশা', 'জনপ্রিয় শিল্পীদের কণ্ঠে হাস্যকৌতুক ও সুরের মূর্ছনায় ভরপুর কালজয়ী বেতার গীতি-নকশা।'],
            'chhaya-shikari-crime' => ['ছায়া শিকারী: বন্দর রহস্য', 'ক্রাইম থ্রিলার সিরিজ', 'চট্টগ্রাম বন্দরের সংরক্ষিত এলাকা থেকে পণ্য চুরির আন্তর্জাতিক সিন্ডিকেটের রহস্য উদ্ঘাটন।'],
            'raater-shongket' => ['রাতের সংকেত', 'গোয়েন্দা ধারাবাহিক নাটক', 'ফরেনসিক বিজ্ঞানের সহায়তায় সিআইডির বিশেষ দলের রোমহর্ষক মামলার সমাধান।'],
            'kuashay-onusondhan' => ['কুয়াশায় অনুসন্ধান', 'রোমাঞ্চকর গোয়েন্দা সিরিজ', 'উত্তরাঞ্চলের কুয়াশাচ্ছন্ন রেলওয়ে জংশনে ট্রেনের সুরক্ষিত বগি থেকে টাকা উধাও হওয়ার রহস্যভেদ।'],
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
            'Dawn at the Estuary' => 'মোহনার ভোর', 'Rising Tides' => 'জোয়ারের গর্জন', 'The New Sanctuary' => 'নতুন অভয়ারণ্য',
            'Secret Transmitters' => 'গোপন ট্রান্সমিটার', 'Across the Sunderbans' => 'সুন্দরবনের জলপথে', 'Voice of the Free Nation' => 'মুক্তিকণ্ঠের বিজয়',
            'Platform 4 Encounter' => 'প্ল্যাটফর্ম চারের দেখা', 'Rush Hour Rhythms' => 'ব্যস্ত সময়ের সুর', 'The Lost Notebook' => 'হারিয়ে যাওয়া নোটবুক',
            'The Char of Shadows' => 'ছায়ার চর', 'Echoes in the Mist' => 'কুয়াশার প্রতিধ্বনি',
            'The Haldi Ceremony' => 'গায়ে হলুদ উৎসব', 'A Surprise Guest' => 'অপ্রত্যাশিত অতিথি',
            'Ransomware Threat' => 'র‍্যানসমওয়্যার আতঙ্ক', 'Counter Strike' => 'পাল্টা আক্রমণ',
            'Morning Studio Broadcast' => 'সকালের স্টুডিও সম্প্রচার', 'Evening Cultural Live' => 'সান্ধ্য সাংস্কৃতিক অনুষ্ঠান',
            'Parliamentary Question Hour' => 'সংসদে প্রশ্নোত্তর পর্ব', 'National Budget Debate' => 'বাজেট বিতর্ক',
            'Full Motion Picture' => 'পূর্ণাঙ্গ চলচ্চিত্র', 'Full Feature Film' => 'পুরো ফিচার ফিল্ম',
            'Blueprint for Bridges' => 'সেতুর ব্লুপ্রিন্ট', 'Electrifying the Delta' => 'নদীমাতৃক দেশের বিদ্যুৎ', 'The First Supercomputer' => 'প্রথম কম্পিউটার অভিযান',
            'The Morning of February' => 'একুশের প্রভাত', 'Winds of Autonomy' => 'স্বাধিকারের বাতাস', 'The Golden Dawn' => 'বিজয়ের স্বর্ণপ্রভাত',
            'Full Short Film' => 'সম্পূর্ণ স্বল্পদৈর্ঘ্য চলচ্চিত্র', 'Poetic Featurette' => 'কাব্যিক চলচ্চিত্রায়ন',
            'Raga Yaman Awakening' => 'রাগ ইমন জাগরণ', 'Bansuri Raga Megh' => 'বাঁশিতে রাগ মেঘ',
            'O Re Neel Doriya' => 'ওরে নীল দরিয়া', 'Majhir Bhaat Gaan' => 'মাঝির ভাটি গান',
            'The Cross Examination' => 'জেরার মুখোমুখি', 'The Letter Across the River' => 'নদী পারের চিঠি', 'The Reunion at Dusk' => 'গোধূলির পুনর্মিলন',
            'The Floating School' => 'ভাসমান পাঠশালা', 'The Graduation Lanterns' => 'আলোর উৎসব',
            'Guardians of the Tide' => 'জোয়ারের প্রহরী', 'Kingdom of the Mangroves' => 'বাদাবনের রাজত্ব',
            'Threads of Jamdani' => 'জামদানির নকশা', 'Clay and Fire' => 'মাটি ও আগুন',
            'Manush Bhojle Shonar Manush Hobi' => 'মানুষ ভজলে সোনার মানুষ হবি', 'Echoes of the Akhra' => 'আখড়ার সুর',
            'The Clever Squirrel' => 'চালাক কাঠবিড়ালি', 'The Honest Woodcutter' => 'সৎ কাঠুরে',
            'Journey to the Moon' => 'চাঁদের দেশে অভিযান', 'Why Rain Falls' => 'বৃষ্টি কেন ঝরে',
            'The Bird and the King' => 'পাখি ও রাজা', 'The Tiger and the Honey Pot' => 'বাঘ ও মধুর হাঁড়ি',
            'World Cup Fever' => 'বিশ্বকাপের উন্মাদনা', 'The Smart Phone Guru' => 'স্মার্টফোনের ওস্তাদ',
            'The Stethoscope Dilemma' => 'স্টেথোস্কোপ বিভ্রান্তি', 'Emergency in the Waiting Room' => 'জরুরি অপেক্ষা',
            'Harvesting the Murta Cane' => 'মুত্রা বেতের সংগ্রহ', 'Patterns of the Lotus' => 'পদ্ম ফুলের নকশা',
            'The Date Palm Tapper' => 'গাছি ও কাঁচা রস', 'Courtyard Fire and Steam' => 'উঠানের পিঠার ধোঁয়া',
            'The Red Border Quilt' => 'লাল পাড়ের নকশী কাঁথা', 'Motifs of the Peacock' => 'ময়ূর মোটিফের নকশা',
            'The Phantom Ferry' => 'ভূতুড়ে খেয়া', 'The Stopped Clock' => 'স্থবির ঘড়িঘর', 'Footsteps on the Roof' => 'ছাদের ওপর পদধ্বনি',
            'Public Transport Overhaul' => 'গণপরিবহন সংস্কার', 'Procurement Transparency' => 'দরপত্র স্বচ্ছতা',
            'Protecting the Consumer' => 'ভোক্তা অধিকার রক্ষা', 'The Delta Blueprint' => 'ডেল্টা মহাপরিকল্পনা',
            'Future of Youth Employment' => 'তরুণ কর্মসংস্থানের ভবিষ্যৎ',
            'Smart Drip Irrigation' => 'আধুনিক ড্রিপ সেচ', 'Organic Bio-Pest Defense' => 'জৈব বালাই দমন',
            'Songs That Ignited Courage' => 'সাহস জাগানো গান', 'Extreme Radio Relays' => 'রণক্ষেত্রের বেতার সম্প্রচার',
            'The Debate in Heaven' => 'স্বর্গের বিতর্ক', 'The Song of Unity' => 'ঐক্যের মহাগান',
            'The Signal Box Phantom' => 'সিগন্যাল বক্সের রহস্য', 'Tracking the Red Wagon' => 'লাল বগির সন্ধানে',
        ];

        foreach ($shows as $position => [$slug, $title, $eyebrow, $description, $category, $image, $year, $rating, $isFeatured, $episodes]) {
            if (! isset($showsBn[$slug])) {
                throw new RuntimeException("Missing Bangla translation for watch show slug: {$slug}");
            }
            [$titleBn, $eyebrowBn, $descriptionBn] = $showsBn[$slug];
            $categoryModel = WatchCategory::query()
                ->where('name', $category)
                ->orWhere('slug', $category)
                ->first();
            $categoryId = $categoryModel?->id;
            $categoryName = $categoryModel?->name ?? $category;

            $show = WatchShow::query()->updateOrCreate(['slug' => $slug], [
                'created_by' => $creatorId,
                'watch_category_id' => $categoryId,
                'title' => $title,
                'title_bn' => $titleBn,
                'eyebrow' => $eyebrow,
                'eyebrow_bn' => $eyebrowBn,
                'description' => $description,
                'description_bn' => $descriptionBn,
                'category' => $categoryName,
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
            WatchClip::query()->updateOrCreate(['slug' => $clipData['slug']], [
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
