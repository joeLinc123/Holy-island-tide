'VBA code to format the tide data
'The starting point is the data that was converted to .csv format from a .pdf file provided by the National Oceanography Centre of predicted high and low tides from Leith (which is used for the Holy Island Crossing)
'The converted csv file contains the following information:
'
'Title Data
'======
'Headings from the pdf file that appears at the start and also when there is a page break
'These items are not required and can be ignored
'The data follows a particular pattern that can be identified separately from the required data. The only data required that includes a character will start with the day name / moon phase(see below) and also include numbers


'Tide times starting with a HIGH tide on the 1st June (Wed) 2022
'=======================================
'The data from this point is fairly well defined (with exceptions)
'The dd element of the date will appear first. It will appear in column 1, with column 2 ALWAYS empty
'The following row will include the day name (M, Tu, W, Th, F, Sa, Su) as the first characters of column 1. This will be combined with a time (HHMM). Column 2 will be the height of the tide in m
'Subsequent rows (until the next dd element) will contain time in column1 (HHMM) and tide height in m.These rows will a repeating pattern of high, low, high, low... etc

'Exceptions:
'----------
'Moon phases. Specific moon phases in the pdf are translated to A,B,C or D. These will appear on a day name, and will be in column 1,prefixed to the day name e.g. AW or BTu
'Page Breaks. When there is a page break in the pdf, the word Time will appear in column 2 before the tide height e.g. Time4.97
'
'These exceptions need to be ignored.


'It will:
'Cleanse the data that has been provided so that the unrequired data is ignored
'Process the data so that the low and high tides are placed into an array. It will also handle cells where there is text/values in the same cell, where moon phase data is included, and where the day is part of the time of a particular tide
'Calculate the safe and unsafe crossing times based on the low and high tides in the array
'Output the safe and unsafe to create a SQL script that can be ran against the crossings table to update the database.

'The processCSVtoSQL subroutine needs to be ran and will do the following in order
'cleanData : Identify data that is not required and mark appropriately
'processData : Read all the required data and create tide high and low information internally. It then calls outputData to display this data on spreadsheet
'updateRange : Will take the tide high and low times, turning them into safe and unsafe crossing times. It will call updateRangeUnSafe and updateRangeSafe separately to process unsafe and safe data respectively
'processSQLFile : Read all the prepared data and create a SQL insert command for the crossing table in the application database




'Type to enable a Day and Time in the same cell to be returned e.g. W0330 ; Day=W, Time=0330 , 0330 Day=<empty>, Time=0330

Type CharNum
    Day As String
    Time As String
End Type

'Type to store relevant tide data

Type TideData
    Day As String
    Month As String
    Year As String
    Time As String
    Height As String
    Type As String
End Type


'Type to enable a from e.g. start date/time and to e.g. end start/time to be held as a range

Type RangeData
    From As TideData
    To As TideData
End Type

Public RangeList() As RangeData   'Array of all crossing range data
Public Months(12) As String
Public Days(7) As String
Sub processCSVtoSQL()

    Call cleanData
    Call processData
    Call updateRange
    Call processSQLFile


End Sub


Sub cleanData()

'Ignore rows that are not required
'This is the first stage of being able to reformat the tide date

'It will identify the following data as not required:
' 1. Cell containing text that does not start with W,T,F,S,M (Day name) and A,B,C,D (moon phase)
' 2. Cell containing text starts with W,T,F,S,M,A,B,C,D and does not contain a number

'The purpose of this process is to mark any line not to be processed with a "*" at the front
'The next part of the process will then work with the lines not marked as"*"

'This will be followed by processData()


Dim row As Integer

row = 1

Do While Trim(Application.Cells(row, 1).Text) <> ""

    'Look for a non character first letter i.e. the day
    
    firstChar = Left(Trim(Application.Cells(row, 1).Text), 1)
    
    If Not IsNumeric(firstChar) Then
        
        'Is a character
        
        If firstChar <> "W" And firstChar <> "T" And firstChar <> "F" And firstChar <> "S" And firstChar <> "M" And firstChar <> "A" And firstChar <> "B" And firstChar <> "C" And firstChar <> "D" Then
        
            '1st character is not a number and Is not the start of the day name, or has the moon information at the beginning of the cell, so not interested in the data
        
            Application.Cells(row, 1).Value = "*" + Trim(Application.Cells(row, 1).Text)
        Else
        
            '1st character is not a number, but does start with the day name / moon status
        
            If HasNumber(Trim(Application.Cells(row, 1).Text)) = False Then  'but, if it doesn't have any numbers in.... not interested. This gets rid of lines where character only in 1st cell
                Application.Cells(row, 1).Value = "*" + Trim(Application.Cells(row, 1).Text)
            End If
            
        End If
        
    End If
    

    row = row + 1

Loop


End Sub
Sub processData()

'Will read all lines to be processed and create the start and end time of the crossing. A array will be created of this data

'It will ignore items where the data in column 1 starts with a *

'The data starts with a High tide on the 1st June 2022 (Wed)
'The process produces a set of "from" data (where a low tide takes place) and "to" data (where the high tide takes place) [1st set of data only contains "to data"]
'The following data will be stored for each set of data:
'name of the day
'dd element of date
'mm element of date
'yyyy element of date
'time element of date
'height of tide
'type of tide (Low/High)

'It should run after cleanData(). The next process to run after this one is outputData(), which is called at the end of this process


Dim myValue As CharNum
Dim row As Integer
Dim rangeIndex As Integer
Dim datesfrom As TideData
Dim datesto As TideData

Dim HighTide As Boolean    'True = High
Dim fromFlag As Boolean    'True = From
Dim firstDay As Boolean    'True = first day has been processed
Dim currentDay As Integer
Dim currentDayString As String
Dim currentMonth As Integer
Dim currentYear As Integer
Dim updatedString As String

' Start point Wed 1st Jun 2022, High Tide

currentDay = 1
currentDayString = ""
currentMonth = 6
currentYear = 2022
HighTide = True
fromFlag = False
firstDay = False
rangeIndex = -1

row = 1


Do While Trim(Application.Cells(row, 1).Text) <> ""

    'Data is cleansed, so ignore data with an * at the start
    
    If Left(Trim(Application.Cells(row, 1).Text), 1) <> "*" Then
    
        'Start Processing here
        'Look for a non character first letter i.e. the day
        
        If IsNumeric(Trim(Application.Cells(row, 1).Text)) And Trim(Application.Cells(row, 2).Text) = "" Then
        
            'This is a day within the month line
            
            currentDay = CInt(Trim(Application.Cells(row, 1).Text))
            If firstDay = False Then
                firstDay = True
            Else
                'Change the month if its a 1
                If currentDay = 1 Then
                    currentMonth = currentMonth + 1
                    If currentMonth = 13 Then
                        currentMonth = 1
                        currentYear = currentYear + 1
                    End If
                End If
            End If
            
        Else
        
            'Now cleanse the data in 1st column and see if it is a Day line
            
            myValue = returnCharNum(Trim(Application.Cells(row, 1).Text))  'This will return the day and time in the cell.
            
            'Now work out if it is from or to
            
            
            If fromFlag = True Then
                'this is a from line (i.e. low tide)
                datesfrom.Height = returnValInString(Trim(Application.Cells(row, 2).Text))
                datesfrom.Day = currentDay
                datesfrom.Month = currentMonth
                datesfrom.Year = currentYear
                datesfrom.Time = myValue.Time
                datesfrom.Type = "Low"
                fromFlag = False
            Else
                'and the to line (i.e high tide)
                datesto.Height = returnValInString(Trim(Application.Cells(row, 2).Text))
                datesto.Day = currentDay
                datesto.Month = currentMonth
                datesto.Year = currentYear
                datesto.Time = myValue.Time
                datesfrom.Type = "High"
                
                'Now update the array, as have the low and high times, and reset the tide data for next set of data
                
                rangeIndex = rangeIndex + 1
                ReDim Preserve RangeList(rangeIndex)
                RangeList(rangeIndex).From = datesfrom
                RangeList(rangeIndex).To = datesto
                Call resetRangeData(datesfrom)
                Call resetRangeData(datesto)
                fromFlag = True
            End If
                
                
        End If
            
        
    End If
    
    

    row = row + 1

Loop


Call outputData




End Sub
Sub updateRange()

'Now that the low and high data has been extracted, safe and unsafe crossing times need to be calculated
'This is because there will be some time leading up to the high tide point for example that is still a safe crossing.

'A simple formula has been applied to create usable data. This could be made more complex at a later point (I was unable to determine the formula from source of the .pdf data)
'Safe Crossing Point
'-----------------
'The midpoint of high tide to low tide, and low tide to high tide is taken. These are the start and endpoint of the safe crossing
'The opposite is true for the unsafe crossing times
'Relative column data is used to perform the calculation. The FormulaR1C1 function on a cell allows a formula to be entered and used
'Using the relative column notification allows date calculations to take place
' e.g.
' Application.Cells(row, 34).FormulaR1C1 = "=RC[-4]-RC[-9]" indicates that cells(row,34) will contain a formula and subtract [current row, current column - 9] from [current row, current column - 4]
' If the current column is Z, then C[-4] is V

'The output of data is circular, e.g. once an unsafe crossing time is calculated, the end of this becomes the start of the safe crossing, and so on...
'The first set of data calculated will be the start and end time of an unsafe crossing
'The second set of data will contain the end point of the next safe crossing(start point is the end of the previous unsafe crossing), and the end point of the next unsafe crossing (start is end of safe crossing) etc....

' Columns (Formats)
' AH : Difference between high and low times (hh:mm:ss)
' AI : Half the difference (hh:mm:ss)
' AJ : High time - AI (hh:mm:ss)
' AK : Date of the tide point (dd/mm/yy)
' AL : Reformatted date if the calculated time is the day before. E.g. if tide occurs on 13/06 at 00:13, but calculated crossing time is 22:39, then the date is adjusted to 12/06
' AN : Difference between low and high times (hh:mm:ss)
' AO : Half the difference (hh:mm:ss)
' AP : High time - AI (hh:mm:ss)
' AQ : Date of tide point (dd/mm/yy)
' AR : Reformatted date if the calculated time is the day before. E.g. if tide occurs on 13/06 at 00:13, but calculated crossing time is 22:39, then the date is adjusted to 12/06


    Dim row As Integer
    
    row = 3
    
    Do While Application.Cells(row, 25).Text <> ""
    
        ' Start by working out the low to high range, therefore the start of UNSAFE crossing for the 1st data, end of SAFE crossing for rest. Put this is columns 34-38
        
        Application.Cells(row, 34).NumberFormat = "hh:mm:ss"
        Application.Cells(row, 34).FormulaR1C1 = "=RC[-4]-RC[-9]"   'High Date/Time - Low Date/Time
        Application.Cells(row, 35).NumberFormat = "hh:mm:ss"
        Application.Cells(row, 35).FormulaR1C1 = "=RC[-1]/2"         'Work out the mid point
        Application.Cells(row, 36).NumberFormat = "hh:mm:ss"
        Application.Cells(row, 36).FormulaR1C1 = "=RC[-6]-RC[-1]"    'Take away the mid point
        Application.Cells(row, 37).NumberFormat = "dd/mm/yyyy"
        Application.Cells(row, 37) = Left(Application.Cells(row, 30).Text, 10) 'Get the date of the latest point (high)
        Application.Cells(row, 38).NumberFormat = "dd/mm/yyyy"
        If CInt(Left(Application.Cells(row, 36).Text, 2)) > CInt(Mid(Application.Cells(row, 30).Text, 12, 2)) Then
            Application.Cells(row, 38).FormulaR1C1 = "=RC[-1]-1"
        Else
            Application.Cells(row, 38).FormulaR1C1 = "=RC[-1]"
        End If
        
        ' Now working out the high to low range, which will be the end of UNSAFE crossing in all cases. Put this is columns 40-38
        
        If row <> 763 Then
            
            Application.Cells(row, 40).NumberFormat = "hh:mm:ss"
            Application.Cells(row, 40).FormulaR1C1 = "=R[+1]C[-15]-RC[-10]"   'Low Date/Time - High Date/Time
            Application.Cells(row, 41).NumberFormat = "hh:mm:ss"
            Application.Cells(row, 41).FormulaR1C1 = "=RC[-1]/2"         'Work out the mid point
            Application.Cells(row, 42).NumberFormat = "hh:mm:ss"
            Application.Cells(row, 42).FormulaR1C1 = "=R[+1]C[-17]-RC[-1]"    'Take away the mid point
            Application.Cells(row, 43).NumberFormat = "dd/mm/yyyy"
            Application.Cells(row, 43) = Left(Application.Cells(row + 1, 25).Text, 10) 'Get the date of the latest point (high)
            Application.Cells(row, 44).NumberFormat = "dd/mm/yyyy"
            If CInt(Left(Application.Cells(row, 42).Text, 2)) > CInt(Mid(Application.Cells(row + 1, 25).Text, 12, 2)) Then
                Application.Cells(row, 44).FormulaR1C1 = "=RC[-1]-1"
            Else
                Application.Cells(row, 44).FormulaR1C1 = "=RC[-1]"
            End If
        End If
    
    
        row = row + 1
    
    Loop
    
    'Increase Column Size
    
    Range("AK1").EntireColumn.AutoFit
    Range("AL1").EntireColumn.AutoFit
    Range("AQ1").EntireColumn.AutoFit
    Range("AR1").EntireColumn.AutoFit
    
    
    'Set up the days table
    
    Call setUpDays
    
    Call updateRangeUnSafe
    Call updateRangeSafe
    

End Sub
Sub processSQLFile()

'Create the SQL import file for the crossings table
'This is the last thing to run, and will create a text file containing the instructions in format
'INSERT INTO crossings VALUES
'(ID,'Start Date/Time (yyyy-mm-dd hm:ss),'Type(safe/unsafe)','End Date/Time (yyyy-mm-dd hm:ss)'),

'Last line will have no comma, instead will have ;
'Excel adds " to the start and end of each line, but is simply replaced post output

'Due to a problem withExcel formatting to dd/mm/yy, dates were incorrect. For example 1st June 2022 would be displayed as 06/01/2022 but was still seen as valid date. Once the date could not be seen as valid e.g. 13th June 2022, the date was corrected to 13/06/2022 (06/13/2022 not valid)
'This was consitent through the data, so the creation of the yyyy-mm-dd is different if the day is <=12
'Column AY row 47 and 48 demonstrates this e.g. 06/12/2022 (which is really 12/06/2022) then 13/06/2022

    Dim row As Integer
    Dim data As String

    Call open_file(1, "K:\import.txt")

    row = 3
    
    Call write_file(1, "INSERT INTO crossings VALUES")
    
    Do While Application.Cells(row, 54).Text <> ""
    
        data = "("
        data = data + Trim(Application.Cells(row, 49).Text) + ","
        If CInt(Left(Trim(Application.Cells(row, 50).Text), 2)) > 12 Then
            data = data + Chr(39) + Right(Trim(Application.Cells(row, 50).Text), 4) + "-" + Mid(Trim(Application.Cells(row, 50).Text), 4, 2) + "-" + Left(Trim(Application.Cells(row, 50).Text), 2) + " " 'use dd/mm/yyyy if day> 12
        Else
            data = data + Chr(39) + Right(Trim(Application.Cells(row, 50).Text), 4) + "-" + Left(Trim(Application.Cells(row, 50).Text), 2) + "-" + Mid(Trim(Application.Cells(row, 50).Text), 4, 2) + " " 'use mm/dd/yyyy if day<=12
        End If
        data = data + Trim(Application.Cells(row, 52).Text) + Chr(39) + ","  'Ensure that the time is included with the date as one value (on database will be a datetime object
        data = data + Chr(39) + Trim(Application.Cells(row, 53).Text) + Chr(39) + ","
        If CInt(Left(Trim(Application.Cells(row, 54).Text), 2)) > 12 Then
            data = data + Chr(39) + Right(Trim(Application.Cells(row, 54).Text), 4) + "-" + Mid(Trim(Application.Cells(row, 54).Text), 4, 2) + "-" + Left(Trim(Application.Cells(row, 54).Text), 2) + " " 'use dd/mm/yyyy if day> 12
        Else
            data = data + Chr(39) + Right(Trim(Application.Cells(row, 54).Text), 4) + "-" + Left(Trim(Application.Cells(row, 54).Text), 2) + "-" + Mid(Trim(Application.Cells(row, 54).Text), 4, 2) + " " 'use mm/dd/yyyy if day<=12
        End If
        data = data + Trim(Application.Cells(row, 55).Text) + Chr(39)
        If Trim(Application.Cells(row + 1, 54).Text) <> "" Then 'Because the last bit of data is not complete
            data = data + "),"
        Else
            data = data + ");"
        End If
    
        Call write_file(1, Replace(data, Chr(34), ""))
        
        row = row + 1
    
    Loop

    Call close_file(1)



End Sub

Sub outputData()

'This process will read data from the rangeList array and populate columns X to AF
'It runs from processData()

'The purpose is to ouput the low and high tide data in a format that can then be used to calculate the crossing times
' (format in brackets were applicable)

'Y  Date and Time (yyyy-mm-dd hh:mm:ss) of low tide
'Z  Time of low tide (hh:mm:ss)
'AA Height of low tide

'AD  Date and Time (yyyy-mm-dd hh:mm:ss) of high tide
'AE  Time of high tide (hh:mm:ss)
'AF Height of high tide


Dim ind As Integer
Dim row As Integer
Dim formatMonth As String
Dim formatDay As String


row = 1


For ind = LBound(RangeList) To UBound(RangeList)

    'Format month so that 1 becomes 01 etc...

    If Len(RangeList(ind).From.Month) = 1 Then
        formatMonth = "0" + RangeList(ind).From.Month
    Else
        formatMonth = RangeList(ind).From.Month
    End If
    
    'Format Day similar to month
    
    If Len(RangeList(ind).From.Day) = 1 Then
        formatDay = "0" + RangeList(ind).From.Day
    Else
        formatDay = RangeList(ind).From.Day
    End If
    
    
    'yyyy-mm-dd hh:mm:ss format (add :00 to end)
    
    Application.Cells(row, 25).NumberFormat = "yyyy-mm-dd hh:mm:ss"
    If RangeList(ind).From.Day <> "" Then
        Application.Cells(row, 25).Value = RangeList(ind).From.Year + "-" + formatMonth + "-" + formatDay + " " + Left(RangeList(ind).From.Time, 2) + ":" + Right(RangeList(ind).From.Time, 2) + ":00"
    End If


    'hh:mm:ss format (add :00 to end)
    Application.Cells(row, 26).NumberFormat = "hh:mm:ss"
    Application.Cells(row, 26).Value = Left(RangeList(ind).From.Time, 2) + ":" + Right(RangeList(ind).From.Time, 2) + ":00"
    
    Application.Cells(row, 27).Value = RangeList(ind).From.Height
    Application.Cells(row, 27).NumberFormat = "0.00"  'Set format of cell to 2dp

    
    'Repeat the above for the high time
    
    If Len(RangeList(ind).To.Month) = 1 Then
        formatMonth = "0" + RangeList(ind).To.Month
    Else
        formatMonth = RangeList(ind).To.Month
    End If
    
    If Len(RangeList(ind).To.Day) = 1 Then
        formatDay = "0" + RangeList(ind).To.Day
    Else
        formatDay = RangeList(ind).To.Day
    End If

    
    'yyyy-mm-dd hh:mm:ss format (add :00 to end)
    Application.Cells(row, 30).NumberFormat = "yyyy-mm-dd hh:mm:ss"
    If RangeList(ind).To.Day <> "" Then
        Application.Cells(row, 30).Value = RangeList(ind).To.Year + "-" + formatMonth + "-" + formatDay + " " + Left(RangeList(ind).To.Time, 2) + ":" + Right(RangeList(ind).To.Time, 2) + ":00"
    End If
    
    'hh:mm:ss format (add :00 to end)
    Application.Cells(row, 31).NumberFormat = "hh:mm:ss"
    Application.Cells(row, 31).Value = Left(RangeList(ind).To.Time, 2) + ":" + Right(RangeList(ind).To.Time, 2) + ":00"
    
    Application.Cells(row, 32).Value = RangeList(ind).To.Height
    Application.Cells(row, 32).NumberFormat = "0.00"
    Application.Cells(row, 33).Value = RangeList(ind).To.Type
    
    row = row + 1

Next ind

'Set Column Widths to auto fit for the date fields

Range("Y1").EntireColumn.AutoFit
Range("AD1").EntireColumn.AutoFit



End Sub


Sub updateRangeUnSafe()

'Process unsafe Crossing times and output them
'Will set the following columns (format in brackets where applicable)
'AW: The id for the database table
'AX: Unsafe crossing start date (dd/mm/yyyy)
'AY: Unsafe crossing start time (hh:mm:ss)
'AZ: Unsafe start time + 1 min (hh:mm:ss)
'BA: Type = Unsafe
'BB: unsafe crossing end date (dd/mm/yyyy)
'BC: unsafe crossing end time (hh:mm:ss)

    Dim row, row2 As Integer
    Dim ind As Integer
    
    row = 3   'output unsafe data to odd rows
    ind = 1
    
    Do While Application.Cells(row, 34).Text <> ""
    
        If row = 3 Then
            row2 = 3
        Else
            row2 = row2 + 2
        End If
        
        Application.Cells(row2, 49) = ind
        ind = ind + 2
        
        Application.Cells(row2, 50).NumberFormat = "dd/mm/yyyy"
        Application.Cells(row2, 50) = Application.Cells(row, 38).Text
        
        Application.Cells(row2, 51).NumberFormat = "hh:mm:ss"
        Application.Cells(row2, 51) = Application.Cells(row, 36).Text
        
        Application.Cells(row2, 52).NumberFormat = "hh:mm:ss"
        Application.Cells(row2, 52).FormulaR1C1 = "=RC[-1]+1/1440"    'Previous column + 1 minute (e.g. + 1 day / 24 hrs / 60 mins)
        
        Application.Cells(row2, 53) = "Unsafe"
        
        Application.Cells(row2, 54).NumberFormat = "dd/mm/yyyy"
        Application.Cells(row2, 54) = Application.Cells(row, 44).Text
        
        Application.Cells(row2, 55).NumberFormat = "hh:mm:ss"
        Application.Cells(row2, 55) = Application.Cells(row, 42).Text
                
        row = row + 1
    
    Loop
    
    'Update Column Widths
    
    Range("AX1").EntireColumn.AutoFit

    
    


End Sub
Sub updateRangeSafe()

'Process Safe Crossing times and ouput them
'Will set the following columns (format in brackets where applicable)
'AW: The id for the database table
'AX: Safe crossing start date (dd/mm/yyyy)
'AY: Safe crossing start time (hh:mm:ss)
'AZ: Safe Start time + 1 minute (hh:mm:ss)
'BA: Type = Safe
'BB: Safe crossing end date (dd/mm/yyyy)
'BC: Safe crossing end time (hh:mm:ss)


    Dim row, row2 As Integer
    Dim ind As Integer
    
    row = 4  'output safe data to even columns
    ind = 2
    
    Do While Application.Cells(row, 34).Text <> ""
    
        If row = 4 Then
            row2 = row
        Else
            row2 = row2 + 2
        End If
    
        Application.Cells(row2, 49) = ind
        ind = ind + 2
        
        Application.Cells(row2, 50).NumberFormat = "dd/mm/yyyy"
        Application.Cells(row2, 50) = Application.Cells(row - 1, 44).Text
        
        Application.Cells(row2, 51).NumberFormat = "hh:mm:ss"
        Application.Cells(row2, 51) = Application.Cells(row - 1, 42).Text
        
        Application.Cells(row2, 52).NumberFormat = "hh:mm:ss"
        Application.Cells(row2, 52).FormulaR1C1 = "=RC[-1]+1/1440"   'previous column + 1 min (e.g. + 1 day / 24 hrs / 60 mins)
        
        Application.Cells(row2, 53) = "Safe"
        
        Application.Cells(row2, 54).NumberFormat = "dd/mm/yyyy"
        Application.Cells(row2, 54) = Application.Cells(row, 38).Text
        
        Application.Cells(row2, 55).NumberFormat = "hh:mm:ss"
        Application.Cells(row2, 55) = Application.Cells(row, 36).Text

                
        row = row + 1
    
    Loop


    'Update Column Widths

    Range("BB1").EntireColumn.AutoFit


End Sub

' OTHER ROUTINES AND FUNCTIONS
' setUpMonths : Create an array of month names
' setUpDays : Create an array of day names 1 = Sunday, 7 = Saturday to match output of the Weekday function
' resetRangeData : Resets set of range (high/low tide) data
' returnCharNum : Split a day character and time, e.g. W0337 returns W and 0337
' returnMonth : Return month from the Months array
' HasNumber : Determine if an input string has a number
' returnValInString : Return a number that is within a string e.g. Tm0337 will return 0337
' open_file, write_file, close_file : text file handlers (for the SQL update command output)

Sub setUpMonths()

'Set up the Months array


Months(0) = "Not Used"
Months(1) = "Jan"
Months(2) = "Feb"
Months(3) = "Mar"
Months(4) = "Apr"
Months(5) = "May"
Months(6) = "Jun"
Months(7) = "Jul"
Months(8) = "Aug"
Months(9) = "Sep"
Months(10) = "Oct"
Months(11) = "Nov"
Months(12) = "Dec"




End Sub
Sub setUpDays()

'Set up the Days array


Days(0) = "Not Used"
Days(1) = "Sun"
Days(2) = "Mon"
Days(3) = "Tue"
Days(4) = "Wed"
Days(5) = "Thu"
Days(6) = "Fri"
Days(7) = "Sat"


End Sub
Sub resetRangeData(inp As TideData)

'Initialise TideData type values

    inp.Day = ""
    inp.Height = ""
    inp.Month = ""
    inp.Time = ""
    inp.Year = ""
    inp.Type = ""


End Sub
Function returnCharNum(inp As String) As CharNum

'This routine will return the Day and Time from an incoming of data e.g. "W0337" will return W as Day and 0337 as time

    Dim retValue As CharNum
    Dim pos As Integer
    
    Dim retDay As String
    Dim retTime As String
    
    retDay = ""  'If there is no Day this will remain empty
    retTime = ""
    
    pos = 1
    
    Do While pos <= Len(inp)
    
        midVal = Mid(inp, pos, 1)  'Get each character one by one
    
        If IsNumeric(midVal) Then
        
            'if numeric, add it to the time value
        
            retTime = retTime + midVal
            
        Else
        
            'Otherwise create day data. Remember to ignore the special characters created for phases of the moon, that were imported from pdf as A,B,C and D
        
            If midVal <> "A" And midVal <> "B" And midVal <> "C" And midVal <> "D" Then
                    retDay = retDay + midVal
            End If
                
        
        End If
    
        pos = pos + 1
    
    Loop
    
    
    retValue.Day = retDay
    
    'Bit of reformatting, to add leading zeroes as needed (midnight would be 00, otherwise single hour needs additional 0)
    
    If Len(retTime) = 2 Then
        retTime = "00" + retTime
    Else
        If Len(retTime) = 3 Then
            retTime = "0" + retTime
        End If
    End If
    
    retValue.Time = retTime
    
    returnCharNum = retValue

End Function
Function returnMonth(forMonth As Integer) As String

    'Function to return the month name for a numeric e.g. 1 = Jan. Uses the Months array set up by setUpMonths() routine

    returnMonth = Months(forMonth)


End Function

Function HasNumber(strData As String) As Boolean

'Function to determine if there is any number within an input string
'It will return true as soon as it finds the1st number e.g. "Time10:00" will return true as soon as the 1 is found. It uses the IsNumeric function on each character in String one by one

    Dim iCnt As Integer
     
    For iCnt = 1 To Len(strData)
        If IsNumeric(Mid(strData, iCnt, 1)) Then
            HasNumber = True
            Exit Function
        End If
    Next iCnt
     
End Function

Function returnValInString(inp As String) As String

'Routine to take a string value and return the value that is contained within
'Purpose is to take a string like "time10.50" and return "10.50"


    Dim retString As String
    Dim pos As Integer
    
    pos = 1
    
    retString = ""
    
    If Not IsNumeric(inp) Then
    
        Do While pos <= Len(inp)
    
            If Not IsNumeric(Mid(inp, pos, 1)) Then
                If Mid(inp, pos, 1) <> "." Then
                    returnValInString = retString
                    Exit Do
                End If
            End If
    
            retString = retString + Mid(inp, pos, 1)
    
            pos = pos + 1
    
        Loop
        
    Else
    
        retString = inp
    
    End If
    
    
    returnValInString = retString
    

End Function





Sub open_file(stream As Integer, path As String)

    Open path For Output As #stream

End Sub

Sub write_file(stream As Integer, data As String)

    Write #stream, data

End Sub



Sub close_file(stream As Integer)

    Close #stream

End Sub


