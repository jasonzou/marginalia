create table mdl_annotation (
	id int primary key auto_increment,
	userid varchar(255) not null,
	access varchar(32) null,
	url varchar(255) not null,
	start_block varchar(255),
	start_xpath varchar(255),
	start_word int,
	start_char int,
	end_block varchar(255),
	end_xpath varchar(255),
	end_word int,
	end_char int,
	note varchar(255) null,
	created datetime not null,	
	modified timestamp not null,
	quote text null,
	quote_title varchar(255) null,
	quote_author varchar(255) null,
	action varchar(30) null,
	link varchar(255) null,
	link_title varchar(255) null,
	version int null,
	object_type varchar(16) null,
	object_id int null
);

create table mdl_annotation_keywords (
	userid bigint(10) unsigned not null,
	name varchar(255) not null,
	description text null
);
