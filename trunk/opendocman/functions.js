function enforceLength(data_str, max_len)
{
	alert(data_str+", "+data_str.length);
	if(data_str.length>max_len)
	{
		data_str = data_str.substring(0, max_len);
	}
	return data_str;
}
