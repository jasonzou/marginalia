create table annotations (
	id int primary key auto_increment,
	userid varchar(255) not null,
	access varchar(32) null,
	url varchar(255) not null,
	start_xpath varchar(255) not null,
	start_block varchar(255) not null,
	start_word int not null,
	start_char int not null,
	end_xpath varchar(255) not null,
	end_block varchar(255) not null,
	end_word int not null,
	end_char int not null,
	note varchar(255) null,
	link varchar(255) null,
	link_title varchar(255) null,
	action varchar(32) null,
	created datetime not null,	
	modified timestamp not null,
	quote text null,
	quote_title varchar(255) null,
	quote_author varchar(255) null
);

create table preferences (
  user bigint primary key,
  name varchar(255),
  value varchar(255)
);

create table keywords (
  name varchar(255) primary key,
  description varchar(255)
);


